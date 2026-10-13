<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Config;

/**
 * Apparence de l'interface choisie par l'utilisateur : un habillage (couleurs
 * de la sidebar et de la navbar) et une couleur primaire.
 *
 * Les habillages sont ceux d'AdminLTE 4, repris à l'identique de
 * vendor/almasaeed2010/adminlte/src/utils/palette.mjs (THEME_PRESETS), plus
 * « nest2prod » : le rendu historique de l'application, porté par
 * public/css/theme-nest2prod.css. Indépendant du mode clair / sombre / pro
 * (App\Support\ThemeMode), qui reste un choix de session.
 */
class UiTheme
{
    public const NEST2PROD = 'nest2prod';

    /**
     * clé => [groupe, navbar (classes, sombre ?), sidebar (classes, thème), primaire].
     * Une sidebar « neutre » (bg-body…) suit le mode clair / sombre de la page.
     */
    public const PRESETS = [
        self::NEST2PROD     => ['group' => 'brand',     'header' => ['bg-body', false],          'sidebar' => ['bg-body', null],                'primary' => null],
        // Clair
        'light'             => ['group' => 'light',     'header' => ['bg-body', false],          'sidebar' => ['bg-body', null],                'primary' => 'primary'],
        'light-indigo'      => ['group' => 'light',     'header' => ['text-bg-indigo', true],    'sidebar' => ['bg-body-tertiary', null],       'primary' => 'indigo'],
        'light-sky'         => ['group' => 'light',     'header' => ['text-bg-sky', true],       'sidebar' => ['bg-body', null],                'primary' => 'sky'],
        // Semi-sombre
        'default'           => ['group' => 'semi-dark', 'header' => ['bg-body', false],          'sidebar' => ['bg-body-secondary', 'dark'],    'primary' => 'primary'],
        'graphite'          => ['group' => 'semi-dark', 'header' => ['bg-body', false],          'sidebar' => ['text-bg-graphite', 'dark'],     'primary' => 'indigo'],
        'navy'              => ['group' => 'semi-dark', 'header' => ['bg-body', false],          'sidebar' => ['text-bg-navy', 'dark'],         'primary' => 'teal'],
        'steel'             => ['group' => 'semi-dark', 'header' => ['bg-body', false],          'sidebar' => ['text-bg-steel', 'dark'],        'primary' => 'sky'],
        'midnight'          => ['group' => 'semi-dark', 'header' => ['bg-body', false],          'sidebar' => ['text-bg-midnight', 'dark'],     'primary' => 'violet'],
        'indigo'            => ['group' => 'semi-dark', 'header' => ['bg-body', false],          'sidebar' => ['text-bg-indigo', 'dark'],       'primary' => 'indigo'],
        // Tout sombre
        'navy-sky'          => ['group' => 'full-dark', 'header' => ['text-bg-sky', true],       'sidebar' => ['text-bg-navy', 'dark'],         'primary' => 'sky'],
        'midnight-mono'     => ['group' => 'full-dark', 'header' => ['text-bg-midnight', true],  'sidebar' => ['text-bg-midnight', 'dark'],     'primary' => 'indigo'],
        'steel-mono'        => ['group' => 'full-dark', 'header' => ['text-bg-steel', true],     'sidebar' => ['text-bg-steel', 'dark'],        'primary' => 'sky'],
        'slate-teal'        => ['group' => 'full-dark', 'header' => ['text-bg-teal', true],      'sidebar' => ['text-bg-slate', 'dark'],        'primary' => 'teal'],
        'graphite-orange'   => ['group' => 'full-dark', 'header' => ['text-bg-orange', true],    'sidebar' => ['text-bg-graphite', 'dark'],     'primary' => 'orange'],
        // Coloré et dégradé
        'violet-mono'       => ['group' => 'coloured',  'header' => ['text-bg-violet', true],    'sidebar' => ['text-bg-violet', 'dark'],       'primary' => 'violet'],
        'gradient-indigo'   => ['group' => 'coloured',  'header' => ['bg-body', false],          'sidebar' => ['bg-gradient-indigo', 'dark'],   'primary' => 'indigo'],
        'gradient-teal'     => ['group' => 'coloured',  'header' => ['bg-body', false],          'sidebar' => ['bg-gradient-teal', 'dark'],     'primary' => 'teal'],
    ];

    public const GROUPS = ['brand', 'light', 'semi-dark', 'full-dark', 'coloured'];

    /**
     * Couleurs primaires proposées : celle de Bootstrap et la palette AdminLTE 4
     * (config adminlte.assets.extended_colors).
     */
    public const PRIMARIES = [
        'primary', 'indigo', 'violet', 'fuchsia', 'pink', 'orange', 'amber',
        'olive', 'teal', 'sky', 'navy', 'steel', 'slate', 'graphite', 'midnight',
    ];

    /**
     * Habillage par défaut : celui de la marque tant que son thème est activé
     * (WEBERP_COLOR_THEME, lu par config/theme.php), sinon celui d'AdminLTE.
     */
    public static function defaultPreset(): string
    {
        return config('theme.palette') === 'nest2prod' ? self::NEST2PROD : 'light';
    }

    public static function preset(?User $user): string
    {
        $preset = $user?->ui_preset;

        return isset(self::PRESETS[$preset]) ? $preset : self::defaultPreset();
    }

    /**
     * Couleur primaire effective : le choix de l'utilisateur, sinon celle de
     * l'habillage. Null = thème de la marque (pas d'attribut data-lte-primary).
     */
    public static function primary(?User $user): ?string
    {
        $primary = $user?->ui_primary;

        if (in_array($primary, self::PRIMARIES, true)) {
            return $primary;
        }

        return self::PRESETS[self::preset($user)]['primary'];
    }

    /**
     * Reporte l'apparence de l'utilisateur dans la configuration AdminLTE, avant
     * le rendu du layout.
     */
    public static function apply(?User $user): void
    {
        $preset = self::PRESETS[self::preset($user)];
        $primary = self::primary($user);

        [$headerClasses, $darkHeader] = $preset['header'];
        [$sidebarClasses, $sidebarTheme] = $preset['sidebar'];

        Config::set('adminlte.classes_topnav', $headerClasses.($darkHeader ? ' navbar-dark' : ''));
        Config::set('adminlte.sidebar_theme', $sidebarTheme);
        // wem-sidebar-neutral : l'élément actif prend la couleur primaire
        // (public/css/wem-shell.css) ; une sidebar colorée garde celui d'AdminLTE.
        Config::set('adminlte.classes_sidebar', $sidebarClasses.' shadow'.(str_starts_with($sidebarClasses, 'bg-body') ? ' wem-sidebar-neutral' : ''));

        // 'primary' est la couleur de Bootstrap : aucun remappage à demander.
        Config::set('adminlte.assets.palette.primary', $primary === 'primary' ? null : $primary);

        // Le thème de la marque redéfinit les couleurs Bootstrap : il ne doit
        // pas masquer un autre habillage ni une autre couleur primaire.
        Config::set('adminlte.plugins.ThemeNest2Prod.active', self::preset($user) === self::NEST2PROD && $primary === null);
    }
}
