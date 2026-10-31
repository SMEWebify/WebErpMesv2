<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GuidesTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wem-guides-'.uniqid();
        config(['guides.path' => $this->root]);

        $this->guide('vente', <<<'MD'
            ---
            title: Parcours de vente
            summary: Du devis à la facture
            category: Ventes
            order: 10
            duration: 20
            updated: 2026-10-25
            ---

            ## Créer le devis

            ![Capture](img/01-devis.png)

            ```steps
            Devis
            Commande
            ```

            > ⚠️ Seul un devis ouvert se modifie.

            ## Créer la commande

            <script>alert(1)</script>
            MD);
        File::put("{$this->root}/vente/img/01-devis.png", 'png');
        File::put("{$this->root}/vente/img/notes.txt", 'texte');

        $this->guide('compta', <<<'MD'
            ---
            title: Export FEC
            category: Comptabilité
            can: accounting-menu
            ---

            Contenu réservé.
            MD);

        Permission::findOrCreate('accounting-menu');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    private function guide(string $slug, string $markdown, string $file = 'guide.md'): void
    {
        File::ensureDirectoryExists("{$this->root}/{$slug}/img");
        File::put("{$this->root}/{$slug}/{$file}", $markdown);
    }

    /** @test */
    public function the_index_lists_the_guides_the_user_may_read()
    {
        $this->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Parcours de vente')
            ->assertSee('Du devis à la facture')
            ->assertDontSee('Export FEC');

        $this->user->givePermissionTo('accounting-menu');

        $this->get(route('guides.index'))->assertSee('Export FEC');
    }

    /** @test */
    public function a_folder_without_title_is_not_a_guide()
    {
        $this->guide('brouillon', "Pas d'en-tête");

        $this->get(route('guides.index'))->assertOk()->assertDontSee('brouillon');
        $this->get(route('guides.show', 'brouillon'))->assertNotFound();
    }

    /** @test */
    public function a_guide_is_rendered_with_its_extensions()
    {
        $response = $this->get(route('guides.show', 'vente'))->assertOk();

        $response->assertSee('id="creer-le-devis"', false)
            ->assertSee('href="#creer-la-commande"', false)
            ->assertSee(route('guides.media', ['slug' => 'vente', 'file' => '01-devis.png']), false)
            ->assertSee('class="guide-steps"', false)
            ->assertSee('guide-callout--warning', false);

        // Le Markdown n'injecte jamais de HTML brut.
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    /** @test */
    public function a_restricted_guide_is_hidden_without_the_permission()
    {
        $this->get(route('guides.show', 'compta'))->assertNotFound();

        $this->user->givePermissionTo('accounting-menu');

        $this->get(route('guides.show', 'compta'))->assertOk()->assertSee('Contenu réservé.');
    }

    /** @test */
    public function the_translation_matching_the_interface_language_is_served()
    {
        $this->guide('vente', "---\ntitle: Sales journey\n---\n\nEnglish body", 'guide.en.md');

        app()->setLocale('en');
        $this->get(route('guides.show', 'vente'))->assertOk()->assertSee('English body');

        app()->setLocale('fr');
        $this->get(route('guides.show', 'vente'))->assertOk()->assertSee('Parcours de vente');
    }

    /** @test */
    public function a_guide_tied_to_an_option_is_hidden_while_the_option_is_off()
    {
        config(['guides.features.bpf' => 'production.require_release', 'production.require_release' => false]);
        $this->guide('bpf', "---\ntitle: Bon pour fabrication\nfeatures: bpf\n---\n\nContenu BPF.");
        $this->guide('inconnue', "---\ntitle: Option inconnue\nfeatures: absente\n---\n\nRien.");

        $this->get(route('guides.index'))->assertOk()->assertDontSee('Bon pour fabrication')->assertDontSee('Option inconnue');
        $this->get(route('guides.show', 'bpf'))->assertNotFound();

        config(['production.require_release' => true]);

        $this->get(route('guides.index'))->assertSee('Bon pour fabrication')->assertDontSee('Option inconnue');
        $this->get(route('guides.show', 'bpf'))->assertOk()->assertSee('Contenu BPF.');
        $this->get(route('guides.show', 'inconnue'))->assertNotFound();
    }

    /** @test */
    public function a_passage_follows_the_state_of_its_option()
    {
        config(['guides.features.bpf' => 'production.require_release', 'production.require_release' => false]);
        $this->guide('atelier', <<<'MD'
            ---
            title: Atelier
            ---

            Avant.

            <!-- feature: bpf -->
            ## Bon pour fabrication

            Play bloqué sans BPF.
            <!-- /feature -->

            <!-- feature: !bpf -->
            Play toujours disponible.
            <!-- /feature -->

            Après.
            MD);

        $this->get(route('guides.show', 'atelier'))->assertOk()
            ->assertSee('Play toujours disponible.')
            ->assertDontSee('Play bloqué sans BPF.')
            ->assertDontSee('bon-pour-fabrication')
            ->assertDontSee('feature:')
            ->assertSee('Après.');

        config(['production.require_release' => true]);

        $this->get(route('guides.show', 'atelier'))->assertOk()
            ->assertSee('Play bloqué sans BPF.')
            ->assertSee('id="bon-pour-fabrication"', false)
            ->assertDontSee('Play toujours disponible.');
    }

    /** @test */
    public function only_images_of_the_guide_folder_are_served()
    {
        $this->get(route('guides.media', ['slug' => 'vente', 'file' => '01-devis.png']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get(route('guides.media', ['slug' => 'vente', 'file' => 'notes.txt']))->assertNotFound();
        $this->get(route('guides.media', ['slug' => 'vente', 'file' => 'absente.png']))->assertNotFound();
        $this->get(route('guides.media', ['slug' => 'compta', 'file' => '01-devis.png']))->assertNotFound();
        $this->get('/fr/guides/vente/media/..%2Fguide.md')->assertNotFound();
    }
}
