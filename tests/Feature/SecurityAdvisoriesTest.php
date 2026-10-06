<?php

namespace Tests\Feature;

use App\Models\Admin\EmailTemplate;
use App\Models\User;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Regression tests for the reports fixed in 2.0.1. Each test names the
 * advisory it closes.
 */
class SecurityAdvisoriesTest extends TestCase
{
    use RefreshDatabase;

    private const XSS = '</textarea><img src=x onerror=alert(document.domain)><script>alert(1)</script><p><b>Bonjour</b></p>';

    /**
     * GHSA-fp5p-mpqh-7fvx: the template body was re-injected raw into the
     * compose editor.
     */
    public function test_an_email_template_never_keeps_executable_markup(): void
    {
        $template = EmailTemplate::create([
            'document_type' => 'quote',
            'subject' => 'Devis',
            'content' => self::XSS,
        ]);

        $stored = $template->fresh()->content;

        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('</textarea>', $stored);
        // Formatting written in the editor survives.
        $this->assertStringContainsString('<b>Bonjour</b>', $stored);
    }

    public function test_a_template_stored_before_the_fix_is_cleaned_when_read(): void
    {
        $template = EmailTemplate::create(['document_type' => 'quote', 'subject' => 'Devis', 'content' => 'x']);
        // Simulate a row written before the mutator existed.
        \DB::table('email_templates')->where('id', $template->id)->update(['content' => self::XSS]);

        $this->assertStringNotContainsString('onerror', $template->fresh()->content);
    }

    public function test_the_sanitizer_keeps_pasted_images(): void
    {
        $html = '<p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==" alt="logo"></p>';

        $this->assertStringContainsString('data:image/png;base64', SafeHtml::clean($html));
    }

    /**
     * GHSA-cvm4-63hj-966j: SVG was accepted on picture endpoints that write to
     * the public disk, where nginx serves it with no CSP.
     */
    public function test_an_svg_is_refused_on_public_picture_uploads(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->actingAs(User::factory()->create());

        $svg = UploadedFile::fake()->createWithContent(
            'poc.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"><rect width="1" height="1"/></svg>'
        );

        $this->post(route('methods.ressource.update.picture', ['id' => 1]), ['id' => 1, 'picture' => $svg])
            ->assertSessionHasErrors('picture');
    }
}
