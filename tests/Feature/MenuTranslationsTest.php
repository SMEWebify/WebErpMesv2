<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Sidebar labels are resolved by AdminLTE's LangFilter against
 * `adminlte::menu.<key>`, not against general_content.php — a key defined only
 * in general_content.php renders as the raw slug in the sidebar.
 *
 * This walks every menu entry of config/adminlte.php so a new one cannot ship
 * untranslated.
 */
class MenuTranslationsTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function menuKeys(): array
    {
        $keys = [];

        $walk = function (array $items) use (&$walk, &$keys) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                foreach (['text', 'header'] as $property) {
                    $value = $item[$property] ?? null;

                    if (is_string($value) && str_ends_with($value, '_trans_key')) {
                        $keys[] = $value;
                    }
                }

                if (!empty($item['submenu']) && is_array($item['submenu'])) {
                    $walk($item['submenu']);
                }
            }
        };

        $walk(config('adminlte.menu', []));

        return array_values(array_unique($keys));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function localeProvider(): array
    {
        return [['fr'], ['en']];
    }

    /**
     * @test
     *
     * @dataProvider localeProvider
     */
    public function every_sidebar_entry_has_a_menu_translation(string $locale)
    {
        app()->setLocale($locale);

        $keys = $this->menuKeys();

        $this->assertNotEmpty($keys, 'No menu entry found in config/adminlte.php.');

        // Guard against a vacuous check: an unknown key must not resolve,
        // otherwise this test would pass whatever the lang files contain.
        $this->assertFalse(Lang::has('adminlte::menu.definitely_not_a_key_trans_key', $locale, false));

        $missing = array_values(array_filter(
            $keys,
            fn (string $key) => !Lang::has("menu.{$key}", $locale, false)
                && !Lang::has("adminlte::menu.{$key}", $locale, false)
        ));

        $this->assertSame(
            [],
            $missing,
            sprintf(
                'Menu keys with no translation in [%s]: %s. Add them to resources/lang/vendor/adminlte/%s/menu.php.',
                $locale,
                implode(', ', $missing),
                $locale
            )
        );
    }
}
