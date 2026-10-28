<?php

namespace App\Services\Quotes;

use App\Models\Methods\MethodsServices;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Collection;

/**
 * Opérations obligatoires d'une catégorie d'affaire : la gamme de chaque ligne
 * article du devis doit contenir chacun des postes de charge exigés.
 *
 * La gamme d'une ligne = ses opérations directes + celles de sa nomenclature,
 * sur toute la profondeur. Comme pour BillOfMaterialsCopier, les éléments
 * rangés sous un sous-ensemble ne portent que `sub_assembly_id` : l'arbre est
 * donc descendu niveau par niveau (une requête par niveau, quelle que soit la
 * taille du devis), chaque sous-ensemble rattaché à sa ligne racine.
 *
 * Les lignes de présentation (section, sous-total, texte) n'ont pas de gamme
 * et sont ignorées.
 */
class RequiredOperationsChecker
{
    /**
     * Postes exigés par la catégorie du devis (vide sans catégorie).
     *
     * @return Collection<int, object{id:int, code:string, label:string}>
     */
    public function requiredServices(Quotes $quote): Collection
    {
        if (! $quote->affair_category_id) {
            return collect();
        }

        return MethodsServices::query()
            ->join('affair_category_required_services as acrs', 'acrs.methods_services_id', '=', 'methods_services.id')
            ->where('acrs.affair_category_id', $quote->affair_category_id)
            ->orderBy('methods_services.ordre')
            ->orderBy('methods_services.code')
            ->toBase()
            ->get(['methods_services.id', 'methods_services.code', 'methods_services.label']);
    }

    /**
     * Lignes article dont la gamme ne contient pas toutes les opérations exigées.
     *
     * @param  int[]|null  $lineIds  lignes à contrôler (null = tout le devis)
     * @return array<int, array{line_id:int, ordre:int, code:?string, label:?string, services:array<int, array{id:int, code:string, label:string}>}>
     */
    public function missing(Quotes $quote, ?array $lineIds = null): array
    {
        $required = $this->requiredServices($quote);
        if ($required->isEmpty()) {
            return [];
        }

        $lines = QuoteLines::query()
            ->where('quotes_id', $quote->id)
            ->articles()
            ->when($lineIds !== null, fn ($q) => $q->whereIn('id', $lineIds))
            ->orderBy('ordre')
            ->toBase()
            ->get(['id', 'ordre', 'code', 'label']);

        if ($lines->isEmpty()) {
            return [];
        }

        $present = $this->servicesByLine($lines->pluck('id')->all());
        $result  = [];

        foreach ($lines as $line) {
            $absent = $required->reject(fn ($service) => isset($present[$line->id][$service->id]));
            if ($absent->isEmpty()) {
                continue;
            }

            $result[] = [
                'line_id'  => (int) $line->id,
                'ordre'    => (int) $line->ordre,
                'code'     => $line->code,
                'label'    => $line->label,
                'services' => $absent->map(fn ($s) => ['id' => (int) $s->id, 'code' => $s->code, 'label' => $s->label])->values()->all(),
            ];
        }

        return $result;
    }

    /**
     * Postes présents dans la gamme de chaque ligne, toute profondeur confondue.
     *
     * @param  int[]  $lineIds
     * @return array<int, array<int, true>>  ligne → [poste => true]
     */
    private function servicesByLine(array $lineIds): array
    {
        $present = array_fill_keys($lineIds, []);

        Task::query()
            ->whereIn('quote_lines_id', $lineIds)
            ->whereNotNull('methods_services_id')
            ->toBase()
            ->get(['quote_lines_id', 'methods_services_id'])
            ->each(function ($task) use (&$present) {
                $present[$task->quote_lines_id][$task->methods_services_id] = true;
            });

        // Sous-ensemble → ligne racine. Un sous-ensemble déjà vu n'est pas
        // revisité : garde-fou contre un cycle dans la nomenclature.
        $owner    = [];
        $frontier = [];
        foreach (SubAssembly::query()->whereIn('quote_lines_id', $lineIds)->toBase()->get(['id', 'quote_lines_id']) as $sub) {
            $owner[$sub->id] = (int) $sub->quote_lines_id;
            $frontier[]      = $sub->id;
        }

        while ($frontier !== []) {
            $next = [];
            foreach (SubAssembly::query()->whereIn('sub_assembly_id', $frontier)->toBase()->get(['id', 'sub_assembly_id']) as $sub) {
                if (isset($owner[$sub->id])) {
                    continue;
                }
                $owner[$sub->id] = $owner[$sub->sub_assembly_id];
                $next[]          = $sub->id;
            }
            $frontier = $next;
        }

        if ($owner !== []) {
            Task::query()
                ->whereIn('sub_assembly_id', array_keys($owner))
                ->whereNotNull('methods_services_id')
                ->toBase()
                ->get(['sub_assembly_id', 'methods_services_id'])
                ->each(function ($task) use (&$present, $owner) {
                    $present[$owner[$task->sub_assembly_id]][$task->methods_services_id] = true;
                });
        }

        return $present;
    }
}
