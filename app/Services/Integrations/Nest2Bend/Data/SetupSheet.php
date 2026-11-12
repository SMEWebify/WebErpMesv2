<?php

namespace App\Services\Integrations\Nest2Bend\Data;

/**
 * Fiche de réglage PDF téléchargée depuis l'URL `fiche` d'un résultat NEST2BEND.
 */
final class SetupSheet
{
    public function __construct(
        public readonly string $contents,
        public readonly string $fileName,
    ) {}

    /**
     * Nom de fichier tiré de Content-Disposition (`filename="fiche-reglage-….pdf"`), réduit à
     * un nom simple terminé par .pdf ; sinon un nom par défaut.
     */
    public static function fileNameFrom(?string $disposition, string $fallback): string
    {
        $name = null;
        if (is_string($disposition)) {
            if (preg_match("/filename\\*\\s*=\\s*UTF-8''([^;]+)/i", $disposition, $m)) {
                $name = rawurldecode(trim($m[1], " \t\""));
            } elseif (preg_match('/filename\s*=\s*"([^"]*)"/i', $disposition, $m) || preg_match('/filename\s*=\s*([^;\s]+)/i', $disposition, $m)) {
                $name = $m[1];
            }
        }

        // Pas de chemin, pas de caractère de contrôle ni de séparateur.
        $name = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\:*?"<>|]+/u', '-', basename(str_replace('\\', '/', (string) $name))) ?? '', " .-");
        if ($name === '') {
            $name = $fallback;
        }
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
            $name .= '.pdf';
        }

        return mb_substr($name, -150);
    }
}
