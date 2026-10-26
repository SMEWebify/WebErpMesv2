<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Le titre des fiches était échappé deux fois : h1="{{ $label }}" passe au composant une
 * chaîne déjà échappée, qu'il échappe encore (« Aciers de l&#039;Ouest » à l'écran).
 */
class PageHeaderEscapingTest extends TestCase
{
    /** @test */
    public function a_bound_title_is_escaped_once()
    {
        $html = Blade::render(
            '<x-Content-header-previous-button :h1="$label" previous="" list="/companies" next=""/>',
            ['label' => "Aciers de l'Ouest"]
        );

        $this->assertStringContainsString("Aciers de l&#039;Ouest", $html);
        $this->assertStringNotContainsString('&amp;#039;', $html);
    }

    /** @test */
    public function no_page_passes_a_free_text_label_as_an_escaped_title()
    {
        $offenders = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => preg_match('/\bh1="[^"]*\{\{[^}]*->label\s*\}\}/', $file->getContents()))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders);
    }
}
