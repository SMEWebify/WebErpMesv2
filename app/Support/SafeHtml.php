<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Nettoyage du HTML saisi dans les éditeurs riches (Summernote).
 *
 * Le contenu d'un modèle d'email est réinjecté tel quel dans l'éditeur, qui le
 * pose en innerHTML : échapper la sortie Blade ne suffit donc pas, un
 * <img onerror> s'exécuterait quand même une fois dans l'éditeur. On ne garde
 * que la mise en forme (gras, listes, tableaux, liens, images), jamais de
 * script ni d'attribut d'événement — GHSA-fp5p-mpqh-7fvx.
 */
class SafeHtml
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return self::purifier()->purify($html);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            // Pas de cache de définitions sur disque : rien à provisionner au
            // déploiement, et le volume (quelques modèles) ne le justifie pas.
            $config->set('Cache.DefinitionImpl', null);
            // Summernote insère les images collées en base64.
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'tel' => true,
                'data' => true,
            ]);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}
