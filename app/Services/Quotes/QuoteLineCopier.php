<?php

namespace App\Services\Quotes;

use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use Illuminate\Support\Facades\DB;

/**
 * Copie profonde d'une ligne de devis, vers le même devis ou un autre :
 * détail technique, gamme, nomenclature (arborescence de sous-ensembles et
 * leurs propres opérations), ressources affectées et fichiers GED.
 *
 * Les opérations et sous-ensembles rattachés à un sous-ensemble ne portent
 * que `sub_assembly_id` (pas `quote_lines_id`) : il faut donc parcourir
 * l'arbre et réécrire chaque clé vers sa copie, sinon la copie perd les
 * niveaux inférieurs ou reste branchée sur la nomenclature d'origine.
 */
class QuoteLineCopier
{
    /**
     * @param  array  $overrides  attributs de la nouvelle ligne (code, label…)
     */
    public function copy(QuoteLines $source, int $targetQuoteId, int $ordre, array $overrides = []): QuoteLines
    {
        return DB::transaction(function () use ($source, $targetQuoteId, $ordre, $overrides) {
            $newLine = $source->replicate();
            $newLine->fill($overrides);
            $newLine->quotes_id = $targetQuoteId;
            $newLine->ordre     = $ordre;
            $newLine->save();

            $details = QuoteLineDetails::where('quote_lines_id', $source->id)->first();
            if ($details) {
                $newDetails = $details->replicate();
                $newDetails->quote_lines_id = $newLine->id;
                $newDetails->save();
            } else {
                QuoteLineDetails::create(['quote_lines_id' => $newLine->id]);
            }

            // Une opération rattachée à un sous-ensemble est copiée avec lui.
            Task::where('quote_lines_id', $source->id)->whereNull('sub_assembly_id')->get()
                ->each(fn (Task $task) => $this->copyTask($task, ['quote_lines_id' => $newLine->id]));

            $copied = [];
            foreach (SubAssembly::where('quote_lines_id', $source->id)->get() as $sub) {
                $this->copySubAssembly(
                    $sub,
                    ['quote_lines_id' => $newLine->id, 'sub_assembly_id' => null],
                    $source->id,
                    $newLine->id,
                    $copied
                );
            }

            $pivots = $source->files->mapWithKeys(fn ($file) => [
                $file->id => [
                    'role'       => $file->pivot->role,
                    'is_primary' => (bool) $file->pivot->is_primary,
                ],
            ])->all();

            if (!empty($pivots)) {
                $newLine->files()->attach($pivots);
            }

            return $newLine;
        });
    }

    private function copyTask(Task $task, array $parent): Task
    {
        $newTask = $task->replicate();
        $newTask->fill($parent);
        $newTask->origin = '5';
        $newTask->save();

        $resources = $task->resources->mapWithKeys(fn ($resource) => [
            $resource->id => [
                'role'        => $resource->pivot->role,
                'source'      => $resource->pivot->source,
                'load_factor' => $resource->pivot->load_factor,
            ],
        ])->all();

        if (!empty($resources)) {
            $newTask->resources()->attach($resources);
        }

        return $newTask;
    }

    /**
     * @param  array<int, true>  $copied  sous-ensembles déjà copiés, garde-fou contre un cycle
     */
    private function copySubAssembly(SubAssembly $sub, array $parent, int $sourceLineId, int $newLineId, array &$copied): void
    {
        if (isset($copied[$sub->id])) {
            return;
        }
        $copied[$sub->id] = true;

        $newSub = $sub->replicate();
        $newSub->fill($parent);
        $newSub->save();

        Task::where('sub_assembly_id', $sub->id)->get()
            ->each(fn (Task $task) => $this->copyTask($task, [
                'sub_assembly_id' => $newSub->id,
                'quote_lines_id'  => $task->quote_lines_id == $sourceLineId ? $newLineId : $task->quote_lines_id,
            ]));

        foreach (SubAssembly::where('sub_assembly_id', $sub->id)->get() as $child) {
            $this->copySubAssembly(
                $child,
                [
                    'sub_assembly_id' => $newSub->id,
                    'quote_lines_id'  => $child->quote_lines_id == $sourceLineId ? $newLineId : $child->quote_lines_id,
                ],
                $sourceLineId,
                $newLineId,
                $copied
            );
        }
    }
}
