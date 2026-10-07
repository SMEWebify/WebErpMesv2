<?php

namespace App\Services\Planning;

use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;

/**
 * Copie la gamme et la nomenclature d'un porteur (ligne de devis, ligne de
 * commande ou produit) vers un autre, sur toute la profondeur de l'arbre.
 *
 * Les opérations et sous-ensembles rangés sous un sous-ensemble ne portent
 * que `sub_assembly_id`, pas la clé de la ligne : se contenter des éléments
 * qui portent la clé de la ligne ne copie que le premier niveau, et les
 * niveaux inférieurs restent branchés sur la nomenclature d'origine. Chaque
 * `sub_assembly_id` est donc réécrit vers sa copie.
 */
class BillOfMaterialsCopier
{
    private string $sourceKey;
    private int $sourceId;
    private string $targetKey;
    private int $targetId;
    private ?string $origin;
    private array $taskOverrides;

    /** @var array<int, true> sous-ensembles déjà copiés, garde-fou contre un cycle */
    private array $copied;

    public const KEYS = ['quote_lines_id', 'order_lines_id', 'products_id'];

    /**
     * @param  string       $sourceKey      une des KEYS
     * @param  string       $targetKey      une des KEYS
     * @param  string|null  $origin         tasks.origin des opérations créées, null pour garder celle d'origine
     * @param  array        $taskOverrides  attributs forcés sur chaque opération créée (ex. status_id)
     * @return int nombre d'opérations créées
     */
    public function copy(string $sourceKey, int $sourceId, string $targetKey, int $targetId, ?string $origin = null, array $taskOverrides = []): int
    {
        if (!in_array($sourceKey, self::KEYS, true) || !in_array($targetKey, self::KEYS, true)) {
            throw new \InvalidArgumentException("Clé de nomenclature inconnue : {$sourceKey} → {$targetKey}");
        }

        $this->sourceKey = $sourceKey;
        $this->sourceId  = $sourceId;
        $this->targetKey = $targetKey;
        $this->targetId  = $targetId;
        $this->origin        = $origin;
        $this->taskOverrides = $taskOverrides;
        $this->copied        = [];

        $count = 0;

        // Une opération rattachée à un sous-ensemble est copiée avec lui.
        foreach (Task::where($sourceKey, $sourceId)->whereNull('sub_assembly_id')->get() as $task) {
            $this->copyTask($task, $this->lineKeys($task));
            $count++;
        }

        foreach (SubAssembly::where($sourceKey, $sourceId)->get() as $sub) {
            $count += $this->copySubAssembly($sub, ['sub_assembly_id' => null] + $this->lineKeys($sub));
        }

        return $count;
    }

    /**
     * Rebranche sur la ligne cible un élément rattaché à la ligne source ;
     * ne touche pas à un élément qui ne l'est pas (simple enfant d'un
     * sous-ensemble).
     */
    private function lineKeys(Task|SubAssembly $item): array
    {
        if ($item->{$this->sourceKey} != $this->sourceId) {
            return [];
        }

        $keys = [$this->targetKey => $this->targetId];
        if ($this->sourceKey !== $this->targetKey) {
            $keys[$this->sourceKey] = null;
        }

        return $keys;
    }

    private function copyTask(Task $task, array $attributes): void
    {
        $newTask = $task->replicate();
        $newTask->fill($this->taskOverrides);
        $newTask->fill($attributes);
        if ($this->origin !== null) {
            $newTask->origin = $this->origin;
        }
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
    }

    /**
     * @return int nombre d'opérations créées sous ce sous-ensemble
     */
    private function copySubAssembly(SubAssembly $sub, array $attributes): int
    {
        if (isset($this->copied[$sub->id])) {
            return 0;
        }
        $this->copied[$sub->id] = true;

        $newSub = $sub->replicate();
        $newSub->fill($attributes);
        $newSub->save();

        $count = 0;

        foreach (Task::where('sub_assembly_id', $sub->id)->get() as $task) {
            $this->copyTask($task, ['sub_assembly_id' => $newSub->id] + $this->lineKeys($task));
            $count++;
        }

        foreach (SubAssembly::where('sub_assembly_id', $sub->id)->get() as $child) {
            $count += $this->copySubAssembly($child, ['sub_assembly_id' => $newSub->id] + $this->lineKeys($child));
        }

        return $count;
    }
}
