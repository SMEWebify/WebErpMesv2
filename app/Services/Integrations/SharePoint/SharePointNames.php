<?php

namespace App\Services\Integrations\SharePoint;

use Illuminate\Support\Str;

/**
 * Noms de dossier et de fichier acceptés par SharePoint Online.
 *
 * Refusés : " * : < > ? / \ |, espaces et points en bord, noms réservés de Windows,
 * préfixe ~$, _vti_. # et % sont remplacés par prudence (anciens sites, liens copiés).
 */
final class SharePointNames
{
    private const MAX_LENGTH = 120;

    private const RESERVED = '/^(con|prn|aux|nul|com[0-9]|lpt[0-9]|desktop\.ini|\.lock|forms)$/i';

    public static function clean(?string $name, string $fallback = 'sans-nom'): string
    {
        $name = (string) $name;
        $name = preg_replace('/[\x00-\x1F"*:<>?\/\\\\|#%]+/u', '_', $name) ?? '';
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        $name = str_replace('_vti_', '_vti', $name);
        $name = trim($name, " .\t");

        if (str_starts_with($name, '~$')) {
            $name = '_' . substr($name, 2);
        }

        if ($name === '') {
            $name = $fallback;
        }

        if (preg_match(self::RESERVED, pathinfo($name, PATHINFO_FILENAME) ?: $name)) {
            $name = '_' . $name;
        }

        return self::limit($name);
    }

    /**
     * Raccourcit en gardant l'extension.
     */
    private static function limit(string $name): string
    {
        if (mb_strlen($name) <= self::MAX_LENGTH) {
            return $name;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $suffix = $extension !== '' && mb_strlen($extension) <= 10 ? '.' . $extension : '';
        $base = mb_substr($name, 0, self::MAX_LENGTH - mb_strlen($suffix));

        return rtrim(Str::of($base)->toString(), ' .') . $suffix;
    }
}
