<?php

namespace Tests\Feature;

use App\Menu\Filters\LocalizedHrefFilter;
use Tests\TestCase;

/**
 * Les audits internes sont déclarés hors du groupe de langue : le lien du menu, préfixé
 * comme les autres (/fr/audit), répondait 404.
 */
class LocalizedMenuLinkTest extends TestCase
{
    /** @test */
    public function a_link_marked_not_localized_keeps_its_url()
    {
        $filter = new LocalizedHrefFilter();

        $audit = $filter->transform(['text' => 'Audits', 'href' => url('/audit'), 'localized' => false]);
        $quality = $filter->transform(['text' => 'Qualité', 'href' => url('/quality')]);

        $this->assertSame(url('/audit'), $audit['href']);
        $this->assertNotSame(url('/quality'), $quality['href']);
    }

    /** @test */
    public function a_language_link_switches_the_language()
    {
        \LaravelLocalization::setLocale('fr');
        $filter = new LocalizedHrefFilter();

        $english = $filter->transform(['text' => 'English', 'href' => url('/en')]);
        $italian = $filter->transform(['text' => 'Italiano', 'href' => url('/it')]);

        $this->assertStringContainsString('/en', $english['href']);
        $this->assertStringContainsString('/it', $italian['href']);
        $this->assertStringNotContainsString('/fr', $italian['href']);
    }

    /** @test */
    public function the_audit_menu_entry_is_not_localized()
    {
        $entry = collect(config('adminlte.menu'))
            ->flatMap(fn ($item) => $item['submenu'] ?? [$item])
            ->firstWhere('url', 'audit');

        $this->assertFalse($entry['localized']);
    }
}
