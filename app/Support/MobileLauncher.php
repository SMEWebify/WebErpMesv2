<?php

namespace App\Support;

/**
 * Met en forme le menu latéral AdminLTE pour le lanceur d'applications mobile
 * (grille d'applications façon Odoo).
 *
 * Le lanceur ne déclare aucun menu propre : il relit les entrées déjà passées
 * par les filtres AdminLTE (droits, URL, traduction, élément actif), donc il
 * ne peut pas diverger de la sidebar desktop.
 */
class MobileLauncher
{
    /**
     * Teinte de la tuile, déduite de l'icon_color de l'entrée de menu.
     */
    private const COLORS = [
        'primary'   => '#3b82f6',
        'info'      => '#0ea5e9',
        'success'   => '#22c55e',
        'warning'   => '#f59e0b',
        'danger'    => '#ef4444',
        'teal'      => '#14b8a6',
        'cyan'      => '#06b6d4',
        'orange'    => '#f97316',
        'purple'    => '#8b5cf6',
        'lime'      => '#84cc16',
        'secondary' => '#64748b',
    ];

    private const DEFAULT_COLOR = '#64748b';

    /**
     * Regroupe les entrées de premier niveau par en-tête de menu.
     *
     * @param  iterable<array>  $items  Résultat de $adminlte->menu('sidebar')
     * @param  string|null  $path  Chemin de la page courante, pour marquer l'entrée active
     * @return array<int, array{title: ?string, apps: array<int, array>}>
     */
    public static function groups(iterable $items, ?string $path = null): array
    {
        $groups = [['title' => null, 'apps' => []]];

        foreach ($items as $item) {
            if (isset($item['header'])) {
                $groups[] = ['title' => $item['header'], 'apps' => []];
                continue;
            }

            if (! isset($item['text']) || (isset($item['type']) && $item['type'] !== '')) {
                continue;
            }

            $children = [];

            foreach ($item['submenu'] ?? [] as $child) {
                if (! isset($child['text']) || isset($child['header']) || empty($child['href'])) {
                    continue;
                }

                $children[] = [
                    'text'        => $child['text'],
                    'href'        => $child['href'],
                    'target'      => $child['target'] ?? null,
                    'active'      => (bool) ($child['active'] ?? false),
                    'label'       => $child['label'] ?? null,
                    'label_color' => $child['label_color'] ?? 'primary',
                ];
            }

            // Un parent sans enfant visible ni lien propre n'ouvre rien.
            $href = $item['href'] ?? null;
            if ($children === [] && (empty($href) || $href === '#')) {
                continue;
            }

            $groups[count($groups) - 1]['apps'][] = [
                'text'     => $item['text'],
                'href'     => $href,
                'target'   => $item['target'] ?? null,
                'icon'     => self::icon($item['icon'] ?? null),
                'color'    => self::COLORS[$item['icon_color'] ?? ''] ?? self::DEFAULT_COLOR,
                'active'   => (bool) ($item['active'] ?? false),
                'children' => $children,
            ];
        }

        $groups = array_values(array_filter($groups, fn ($group) => $group['apps'] !== []));

        return $path === null ? $groups : self::markActive($groups, $path);
    }

    /**
     * Marque l'entrée correspondant à la page courante.
     *
     * L'ActiveFilter d'AdminLTE compare url('orders') à l'URL demandée, qui porte
     * le préfixe de langue (/fr/orders) : il ne trouve jamais rien. On compare ici
     * les chemins sans ce préfixe, et l'entrée la plus précise l'emporte
     * (/quality/action plutôt que /quality).
     */
    private static function markActive(array $groups, string $path): array
    {
        $path = self::stripLocale($path);
        $best = null;
        $bestLength = -1;

        foreach ($groups as $g => $group) {
            foreach ($group['apps'] as $a => $app) {
                $candidates = [[null, $app['href']]];

                foreach ($app['children'] as $c => $child) {
                    $candidates[] = [$c, $child['href']];
                }

                foreach ($candidates as [$c, $href]) {
                    $target = self::stripLocale((string) parse_url((string) $href, PHP_URL_PATH));

                    if ($target === '' || ($path !== $target && ! str_starts_with($path, $target.'/'))) {
                        continue;
                    }

                    // À égalité, un sous-menu l'emporte sur le lien de son parent
                    $length = strlen($target);
                    $childOfBest = $best !== null && $c !== null && $best[2] === null && [$best[0], $best[1]] === [$g, $a];

                    if ($length > $bestLength || ($length === $bestLength && $childOfBest)) {
                        $best = [$g, $a, $c];
                        $bestLength = strlen($target);
                    }
                }
            }
        }

        if ($best === null) {
            return $groups;
        }

        foreach ($groups as $g => $group) {
            foreach ($group['apps'] as $a => $app) {
                $groups[$g]['apps'][$a]['active'] = [$g, $a] === [$best[0], $best[1]];

                foreach ($app['children'] as $c => $child) {
                    $groups[$g]['apps'][$a]['children'][$c]['active'] = $best === [$g, $a, $c];
                }
            }
        }

        return $groups;
    }

    private static function stripLocale(string $path): string
    {
        $segments = explode('/', trim($path, '/'));

        if (in_array($segments[0], array_keys(config('laravellocalization.supportedLocales', [])), true)) {
            array_shift($segments);
        }

        return implode('/', $segments);
    }

    /**
     * L'application de la page courante, pour l'afficher dans la barre du haut.
     */
    public static function current(array $groups): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['apps'] as $app) {
                if ($app['active']) {
                    return $app;
                }
            }
        }

        return null;
    }

    /**
     * Retire la classe "nav-icon" propre à la sidebar.
     */
    private static function icon(?string $icon): string
    {
        $icon = trim(str_replace('nav-icon', '', (string) $icon));

        return $icon !== '' ? $icon : 'fas fa-circle';
    }
}
