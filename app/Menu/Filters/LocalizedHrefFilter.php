<?php

namespace App\Menu\Filters;

use JeroenNoten\LaravelAdminLte\Helpers\MenuItemHelper;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Ajoute la langue courante aux liens du menu (/companies -> /fr/companies).
 *
 * Le HrefFilter d'AdminLTE construit les liens avec url(), sans préfixe de
 * langue : le clic passait par une redirection de mcamara, et l'ActiveFilter,
 * qui compare le lien à l'URL de la page, ne mettait jamais l'entrée courante
 * en évidence. À placer juste après HrefFilter dans config/adminlte.php.
 */
class LocalizedHrefFilter implements FilterInterface
{
    public function transform($item)
    {
        if (MenuItemHelper::isHeader($item) || empty($item['href']) || $item['href'] === '#') {
            return $item;
        }

        // Seuls les liens internes à l'application sont localisés.
        if (! str_starts_with($item['href'], url('/'))) {
            return $item;
        }

        $localized = LaravelLocalization::getLocalizedURL(null, $item['href']);

        if (is_string($localized) && $localized !== '') {
            $item['href'] = $localized;
        }

        return $item;
    }
}
