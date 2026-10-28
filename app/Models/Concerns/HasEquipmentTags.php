<?php

namespace App\Models\Concerns;

use App\Casts\EquipmentTags;
use Illuminate\Database\Eloquent\Builder;

/**
 * Repères d'équipement (tags) des lignes de devis, de commande et d'ARC.
 *
 * `equipment_tag` est une liste (voir EquipmentTags) : une ligne de quantité
 * > 1 peut porter un repère par équipement. Une ligne de présentation n'en
 * porte jamais.
 */
trait HasEquipmentTags
{
    public function initializeHasEquipmentTags(): void
    {
        $this->mergeCasts(['equipment_tag' => EquipmentTags::class]);
        $this->mergeFillable(['equipment_tag']);
    }

    public static function bootHasEquipmentTags(): void
    {
        static::saving(function ($line) {
            if (array_key_exists('line_type', $line->getAttributes()) && !$line->isArticle()) {
                $line->equipment_tag = null;
            }
        });
    }

    /**
     * Lignes dont un repère contient le texte cherché. Recherche partielle,
     * insensible à la casse avec la collation de la table.
     */
    public function scopeWithEquipmentTag(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        // « ! » plutôt que « \ » : SQLite n'a pas de caractère d'échappement par défaut
        // et MySQL lit « \ » différemment selon NO_BACKSLASH_ESCAPES.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return $query->whereRaw(
            $query->getGrammar()->wrap($query->qualifyColumn('equipment_tag')) . " LIKE ? ESCAPE '!'",
            ['%' . $escaped . '%']
        );
    }

    /**
     * Repères affichés sur une ligne d'impression ou d'écran : « A, B, C ».
     */
    public function equipmentTagsText(): string
    {
        return implode(', ', $this->equipment_tag);
    }
}
