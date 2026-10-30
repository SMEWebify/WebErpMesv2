<?php

namespace App\Services\Products;

use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\User;

/**
 * Lecture / écriture de la base article.
 *
 * products-menu donne la lecture (liste, fiche, gamme, nomenclature, GED) ;
 * products-edit l'écriture, réservée au service technique. Les commerciaux
 * gardent les sélecteurs d'article et l'import d'un article gammé dans une
 * ligne de devis ou de commande, qui ne modifient pas l'article.
 *
 * La gamme et la nomenclature d'un article passent par le même écran que celles
 * des lignes de devis / commande (TaskManageApiController) : c'est l'objet
 * visé (tâche, sous-ensemble) qui dit s'il appartient à un article, jamais le
 * seul type annoncé dans l'URL.
 */
class ProductCatalogAccess
{
    public const EDIT_PERMISSION = 'products-edit';

    /** Garde-fou contre une boucle dans les sous-ensembles. */
    private const MAX_DEPTH = 50;

    public static function canEdit(?User $user): bool
    {
        return $user !== null && $user->can(self::EDIT_PERMISSION);
    }

    /**
     * Le contexte d'écriture (type + identifiant de la ligne) est-il un article ?
     */
    public static function contextIsProduct(string $idType, string|int|null $idLine): bool
    {
        return match ($idType) {
            'products_id' => true,
            'sub_assembly_id' => self::subAssemblyIsProduct(SubAssembly::find($idLine)),
            default => false,
        };
    }

    public static function taskIsProduct(Task $task): bool
    {
        if ($task->products_id) {
            return true;
        }

        return $task->sub_assembly_id
            ? self::subAssemblyIsProduct(SubAssembly::find($task->sub_assembly_id))
            : false;
    }

    /**
     * Un sous-ensemble appartient à un article s'il y est rattaché directement,
     * ou si le sous-ensemble racine de sa chaîne l'est (les niveaux inférieurs
     * ne portent que sub_assembly_id).
     */
    public static function subAssemblyIsProduct(?SubAssembly $subAssembly): bool
    {
        $depth = 0;

        while ($subAssembly !== null && $depth++ < self::MAX_DEPTH) {
            if ($subAssembly->products_id) {
                return true;
            }

            if ($subAssembly->quote_lines_id || $subAssembly->order_lines_id || ! $subAssembly->sub_assembly_id) {
                return false;
            }

            $subAssembly = SubAssembly::find($subAssembly->sub_assembly_id);
        }

        return false;
    }
}
