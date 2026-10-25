<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Cast « encrypted » qui relit aussi les anciennes valeurs chiffrées avec encrypt() :
 * celui-ci sérialise avant de chiffrer, si bien que decryptString() rendait
 * « s:25:"FR76 …"; » pour un IBAN et « N; » pour un BIC vide. Écriture inchangée
 * (encryptString) ; une ancienne valeur est réécrite proprement au prochain enregistrement.
 */
class EncryptedLegacyString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $plain = Crypt::decryptString($value);

        // Seules les formes produites par serialize() pour une chaîne ou null sont dépliées.
        if ($plain === 'N;') {
            return null;
        }
        if (preg_match('/^s:\d+:".*";$/s', $plain)) {
            $unwrapped = @unserialize($plain, ['allowed_classes' => false]);

            return is_string($unwrapped) ? $unwrapped : $plain;
        }

        return $plain;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Crypt::encryptString((string) $value);
    }
}
