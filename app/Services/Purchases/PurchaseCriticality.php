<?php

namespace App\Services\Purchases;

use Illuminate\Validation\Rule;

/**
 * Criticité d'un achat, portée par l'article et recopiée sur la ligne d'achat :
 * type de certificat EN 10204, code de construction, niveau nucléaire.
 * Valeurs proposées : config/purchase_quality.php.
 */
final class PurchaseCriticality
{
    public const FIELDS = ['certificate_type', 'construction_code', 'nuclear_level'];

    private const CONFIG_KEYS = [
        'certificate_type'  => 'certificate_types',
        'construction_code' => 'construction_codes',
        'nuclear_level'     => 'nuclear_levels',
    ];

    /** @return array<string, list<string>> */
    public static function options(): array
    {
        $options = [];
        foreach (self::CONFIG_KEYS as $field => $key) {
            $options[$field] = array_values((array) config('purchase_quality.' . $key, []));
        }

        return $options;
    }

    /**
     * Règles de validation. $current : valeurs déjà portées, acceptées même retirées
     * de la configuration depuis (une ligne ancienne reste enregistrable).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(array $current = []): array
    {
        $rules = [];
        foreach (self::options() as $field => $values) {
            $allowed = array_filter(array_merge($values, [$current[$field] ?? null]));
            $rules[$field] = ['nullable', 'string', Rule::in($allowed)];
        }

        return $rules;
    }

    /** @return array<string, string|null> */
    public static function of(object $model): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $value = $model->{$field} ?? null;
            $values[$field] = $value === '' ? null : $value;
        }

        return $values;
    }
}
