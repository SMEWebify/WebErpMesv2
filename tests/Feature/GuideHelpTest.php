<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Guides\GuideLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * « ? » des écrans vers une section de guide (<x-guide-help>, GuideHelp.jsx).
 */
class GuideHelpTest extends TestCase
{
    use RefreshDatabase;

    private const USAGE = '#<(?:x-guide-help|GuideHelp)\s+guide="([a-z0-9-]+)"(?:\s+section="([a-z0-9-]+)")?#';

    /** @test */
    public function every_help_link_of_the_code_points_to_an_existing_section_in_each_language()
    {
        $files = Finder::create()->files()->in([resource_path('views'), resource_path('js/components'), resource_path('js/lib')])
            ->name(['*.blade.php', '*.jsx'])->notName('guide-help.blade.php')->notName('GuideHelp.jsx');

        $broken = [];
        $count = 0;
        foreach ($files as $file) {
            preg_match_all(self::USAGE, $file->getContents(), $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $count++;
                [$guide, $section] = [$m[1], $m[2] ?? null];
                $dir = resource_path("guides/{$guide}");
                $versions = glob("{$dir}/guide*.md") ?: [];
                if ($versions === []) {
                    $broken[] = "{$file->getRelativePathname()} : guide « {$guide} » introuvable";
                    continue;
                }
                foreach ($versions as $version) {
                    if ($section && !preg_match('/^## .*\{#'.preg_quote($section, '/').'\}\s*$/m', File::get($version))) {
                        $broken[] = "{$file->getRelativePathname()} : {#{$section}} absente de {$guide}/".basename($version);
                    }
                }
            }
        }

        $this->assertSame([], $broken, "Un « ? » vise une section qui n'existe pas :\n".implode("\n", $broken));
        $this->assertGreaterThan(0, $count, "Aucun « ? » trouvé : l'expression de recherche ne correspond plus à l'usage.");
    }

    /** @test */
    public function an_explicit_anchor_becomes_the_section_id()
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wem-guide-help-'.uniqid();
        File::ensureDirectoryExists("{$root}/achats");
        File::put("{$root}/achats/guide.md", "---\ntitle: Achats\ncan: purchases-menu\n---\n\n![Capture](img/01.png)\n\n## 5. Réceptionner {#reception}\n\nTexte.");
        // Capture réelle de 3 × 2 px : ses dimensions réservent sa place avant le chargement différé.
        File::ensureDirectoryExists("{$root}/achats/img");
        imagepng(imagecreatetruecolor(3, 2), "{$root}/achats/img/01.png");
        config(['guides.path' => $root]);

        try {
            Permission::findOrCreate('purchases-menu');
            $user = User::factory()->create();
            $this->assertNull((new GuideLibrary())->helpLink($user, 'achats', 'reception'));

            $user->givePermissionTo('purchases-menu');
            $user = $user->fresh();
            $library = new GuideLibrary();
            $html = $library->render($library->find('achats', $user))['html'];
            $this->assertStringContainsString('<h2 id="reception">5. Réceptionner</h2>', $html);
            // Sans dimensions, les captures chargées au-dessus de l'ancre repoussaient la page.
            $this->assertStringContainsString('class="guide-img" width="3" height="2"', $html);
            $this->assertSame(route('guides.show', 'achats').'#reception', $library->helpLink($user, 'achats', 'reception')['url']);

            $this->actingAs($user);
            $rendered = Blade::render('<x-guide-help guide="achats" section="reception" />');
            $this->assertStringContainsString('href="'.route('guides.show', 'achats').'#reception"', $rendered);
            $this->assertSame('', trim(Blade::render('<x-guide-help guide="absent" />')));
        } finally {
            File::deleteDirectory($root);
        }
    }
}
