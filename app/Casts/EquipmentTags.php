<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Repères d'équipement d'une ligne : liste de chaînes stockée en JSON.
 *
 * Lecture : toujours un tableau (vide si rien n'est renseigné). Écriture :
 * accepte un tableau ou une chaîne séparée par virgule, point-virgule ou
 * retour à la ligne ; repères rognés, vides et doublons retirés, null quand
 * la liste est vide. Le JSON est écrit sans échapper « / » ni les accents,
 * sinon une recherche LIKE sur « 1RCP/001 » ne retrouverait pas « 1RCP\/001 ».
 */
class EquipmentTags implements CastsAttributes
{
    /** Règles de validation du champ, partagées par les écrans de lignes devis et commande. */
    public const RULES = [
        'equipment_tag'   => 'nullable|array|max:500',
        'equipment_tag.*' => 'nullable|string|max:100',
    ];

    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? self::normalize($decoded) : self::normalize((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $tags = self::normalize($value);

        return $tags === [] ? null : json_encode($tags, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return string[]
     */
    public static function normalize(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        $items = is_array($value) ? $value : preg_split('/[,;\r\n]+/', (string) $value);

        $tags = [];
        foreach ($items as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $tag = trim((string) $item);
            if ($tag !== '' && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
