<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La fiche inventaire lisait 28 libellés que la vue ne lui transmettait pas : « undefined »,
 * en-têtes de colonnes et bandeaux vides. Chaque trans.x du composant doit être fourni.
 */
class InventoryShowTranslationsTest extends TestCase
{
    /** @test */
    public function every_label_read_by_the_inventory_screen_is_provided()
    {
        $component = file_get_contents(resource_path('js/components/inventories/InventoryShow.jsx'));
        $view = file_get_contents(resource_path('views/products/inventories/show.blade.php'));

        preg_match_all('/\btrans\.([a-z_0-9]+)/', $component, $used);
        preg_match_all("/'([a-z_0-9]+)'\s*=>/", $view, $given);

        $this->assertSame([], array_values(array_diff(array_unique($used[1]), $given[1])));
    }
}
