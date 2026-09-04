<?php

namespace App\Support;

use JeroenNoten\LaravelAdminLte\Http\Controllers\DarkModeController;

/**
 * Mode d'affichage de l'interface, choisi par l'utilisateur depuis la navbar.
 *
 * Trois valeurs : "light", "dark" et "pro" (chrome neutre, couleur conservée
 * uniquement sur les statuts). La préférence vit en session, comme le dark mode
 * natif d'AdminLTE dont elle reste le sur-ensemble : la clé de session AdminLTE
 * est tenue synchronisée pour que le layout continue de poser "dark-mode" seul.
 */
class ThemeMode
{
    public const SESSION_KEY = 'wem_theme_mode';

    public const LIGHT = 'light';

    public const DARK = 'dark';

    public const PRO = 'pro';

    /**
     * Les modes disponibles, dans l'ordre de rotation du widget navbar.
     */
    public const MODES = [self::LIGHT, self::DARK, self::PRO];

    /**
     * Le mode courant.
     */
    public static function current(): string
    {
        $mode = session(self::SESSION_KEY);

        if (in_array($mode, self::MODES, true)) {
            return $mode;
        }

        // Aucun choix explicite (session ouverte avant l'arrivée du mode pro,
        // ou tout premier affichage) : on retombe sur la préférence AdminLTE.
        return (new DarkModeController())->isEnabled() ? self::DARK : self::LIGHT;
    }

    /**
     * Enregistre le mode et synchronise la préférence dark mode d'AdminLTE.
     */
    public static function set(string $mode): string
    {
        if (! in_array($mode, self::MODES, true)) {
            $mode = self::LIGHT;
        }

        session([self::SESSION_KEY => $mode]);

        $darkMode = new DarkModeController();

        $mode === self::DARK ? $darkMode->enable() : $darkMode->disable();

        return $mode;
    }

    /**
     * La classe à ajouter sur le tag body. AdminLTE gère déjà "dark-mode".
     */
    public static function bodyClass(): string
    {
        return self::current() === self::PRO ? 'theme-pro' : '';
    }
}
