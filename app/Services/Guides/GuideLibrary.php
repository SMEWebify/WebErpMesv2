<?php

namespace App\Services\Guides;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Guides utilisateur rangés sur disque, un dossier par guide (config/guides.php) :
 *
 *   {slug}/guide.md         en-tête YAML + contenu Markdown (langue par défaut, le français)
 *   {slug}/guide.en.md      traduction facultative, servie si la langue de l'interface correspond
 *   {slug}/img/*.png        captures, référencées en `img/xx.png` dans le Markdown
 *
 * En-tête : title (obligatoire), summary, category, order, duration (minutes), icon
 * (classe Font Awesome), can (permission ou liste de permissions, une seule suffit),
 * updated (AAAA-MM-JJ), features (option ou liste d'options de config('guides.features'),
 * toutes requises : le guide est masqué tant qu'une est désactivée).
 *
 * Extensions du Markdown, pour rester lisible dans un éditeur comme dans l'appli :
 * - bloc ```steps (une étape par ligne) → frise d'étapes ;
 * - citation commençant par ⚠️ ou 💡 → encadré « attention » / « astuce » ;
 * - `<!-- feature: nom -->` … `<!-- /feature -->` → passage affiché seulement si l'option
 *   est active (`!nom` : seulement si elle ne l'est pas). Blocs non imbriquables ;
 * - `## Titre {#ancre}` → ancre explicite de la section, la même dans chaque langue : c'est
 *   elle que visent les « ? » de l'application (`<x-guide-help>`, `GuideHelp.jsx`).
 *
 * Ajouter un guide = déposer un dossier : ni migration, ni route, ni code.
 */
class GuideLibrary
{
    private const SLUG = '/^[a-z0-9][a-z0-9-]*$/';

    private const IMAGE = '/^[A-Za-z0-9._-]+\.(png|jpe?g|webp|gif)$/i';

    private const ANCHOR = '/\s*\{#([a-z][a-z0-9-]*)\}\s*$/';

    /**
     * Liens des « ? » par utilisateur et langue. Le service est enregistré en `scoped`
     * (AppServiceProvider) : ce cache ne vit que le temps d'une requête.
     *
     * @var array<string, array<string, array{url: string, label: string}>>
     */
    private array $helpLinks = [];

    public function root(): string
    {
        return rtrim(config('guides.path'), '/\\');
    }

    /**
     * Guides visibles par l'utilisateur, triés par catégorie puis par ordre.
     *
     * @return Collection<int, Guide>
     */
    public function visibleTo(?User $user, ?string $locale = null): Collection
    {
        $dirs = is_dir($this->root()) ? glob($this->root().'/*', GLOB_ONLYDIR) : [];

        return collect($dirs ?: [])
            ->map(fn (string $dir) => $this->load(basename($dir), $locale))
            ->filter(fn (?Guide $guide) => $guide && $this->allows($user, $guide) && $this->featuresEnabled($guide))
            ->sortBy([['category', 'asc'], ['order', 'asc'], ['title', 'asc']])
            ->values();
    }

    public function find(string $slug, ?User $user, ?string $locale = null): ?Guide
    {
        $guide = $this->load($slug, $locale);

        return $guide && $this->allows($user, $guide) && $this->featuresEnabled($guide) ? $guide : null;
    }

    /**
     * Guides lisibles par l'utilisateur → lien et libellé du « ? » qui y renvoie. Calculé une
     * fois par requête : le gabarit le sert aux écrans React, `<x-guide-help>` le relit.
     *
     * @return array<string, array{url: string, label: string}>
     */
    public function helpLinks(?User $user, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $key = ($user?->getKey() ?? 'guest').'|'.$locale;

        return $this->helpLinks[$key] ??= $this->visibleTo($user, $locale)
            ->mapWithKeys(fn (Guide $guide) => [$guide->slug => [
                'url' => route('guides.show', $guide->slug),
                'label' => __('guides.help', ['title' => $guide->title], $locale),
            ]])
            ->all();
    }

    /**
     * Lien du « ? » vers une section d'un guide, ou null si l'utilisateur ne peut pas le lire.
     *
     * @return array{url: string, label: string}|null
     */
    public function helpLink(?User $user, string $slug, ?string $section = null): ?array
    {
        $link = $this->helpLinks($user)[$slug] ?? null;

        if ($link && $section !== null && $section !== '') {
            $link['url'] .= '#'.$section;
        }

        return $link;
    }

    /**
     * @return array{html: string, toc: array<int, array{id: string, text: string}>}
     */
    public function render(Guide $guide): array
    {
        $content = $this->parse($guide->file)['content'];

        // Avant la conversion : un titre retiré ne doit pas entrer dans le sommaire.
        $content = preg_replace_callback(
            '#<!--\s*feature:\s*(!?)\s*([a-z0-9-]+)\s*-->(.*?)<!--\s*/feature\s*-->\R?#s',
            fn (array $m) => $this->featureEnabled($m[2]) !== ($m[1] === '!') ? $m[3] : '',
            $content
        );

        $html = (new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]))->convert($content)->getContent();

        $toc = [];
        $html = preg_replace_callback('#<h2>(.*?)</h2>#s', function (array $m) use (&$toc) {
            $anchor = null;
            if (preg_match(self::ANCHOR, $m[1], $a)) {
                $anchor = $a[1];
                $m[1] = preg_replace(self::ANCHOR, '', $m[1]);
            }
            $text = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5));
            $id = $anchor ?? (Str::slug($text) ?: 'section');
            // « 9. Facturer » : un identifiant CSS ne commence pas par un chiffre (querySelector le refuse).
            if (ctype_digit($id[0])) {
                $id = "section-{$id}";
            }
            $base = $id;
            for ($i = 2; in_array($id, array_column($toc, 'id'), true); $i++) {
                $id = "{$base}-{$i}";
            }
            $toc[] = ['id' => $id, 'text' => $text];

            return "<h2 id=\"{$id}\">{$m[1]}</h2>";
        }, $html);

        $html = preg_replace_callback('#<img src="img/([^"]+)"#', function (array $m) use ($guide) {
            if (!preg_match(self::IMAGE, $m[1])) {
                return $m[0];
            }

            $url = route('guides.media', ['slug' => $guide->slug, 'file' => $m[1]]);

            // Dimensions réelles : sans elles une capture en chargement différé vaut 0 px, et
            // celles qui se chargent au-dessus d'une ancre (#section) repoussent la page.
            $size = ($path = $this->mediaPath($guide, $m[1])) ? @getimagesize($path) : false;
            $dims = $size ? ' width="'.$size[0].'" height="'.$size[1].'"' : '';

            return '<img loading="lazy" class="guide-img"'.$dims.' src="'.e($url).'"';
        }, $html);

        $html = preg_replace_callback('#<pre><code class="language-steps">(.*?)</code></pre>#s', function (array $m) {
            $steps = array_values(array_filter(array_map('trim', explode("\n", $m[1])), 'strlen'));
            $items = array_map(
                fn (string $step, int $i) => '<li class="guide-steps__item"><span class="guide-steps__num">'.($i + 1).'</span>'.$step.'</li>',
                $steps,
                array_keys($steps)
            );

            return '<ol class="guide-steps">'.implode('', $items).'</ol>';
        }, $html);

        // Encadrés : la classe suit le premier marqueur, chaque marqueur devient une icône.
        $html = preg_replace_callback('#<blockquote>\s*<p>(⚠️|💡)#u', fn (array $m) => '<blockquote class="guide-callout guide-callout--'
            .($m[1] === '💡' ? 'tip' : 'warning').'"><p>'.$m[1], $html);
        $html = str_replace(
            ['<p>⚠️ ', '<p>💡 '],
            ['<p><i class="fas fa-triangle-exclamation guide-callout__icon"></i> ', '<p><i class="fas fa-lightbulb guide-callout__icon"></i> '],
            $html
        );

        $html = str_replace('<table>', '<div class="table-responsive"><table class="table table-sm table-bordered">', $html);
        $html = str_replace('</table>', '</table></div>', $html);

        return ['html' => $html, 'toc' => $toc];
    }

    /**
     * Chemin absolu d'une capture du guide, ou null si le nom n'est pas une image du dossier img/.
     */
    public function mediaPath(Guide $guide, string $file): ?string
    {
        if (!preg_match(self::IMAGE, $file)) {
            return null;
        }

        $path = dirname($guide->file).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$file;

        return is_file($path) ? $path : null;
    }

    private function load(string $slug, ?string $locale): ?Guide
    {
        if (!preg_match(self::SLUG, $slug)) {
            return null;
        }

        $dir = $this->root().DIRECTORY_SEPARATOR.$slug;
        $locale = $locale ?? app()->getLocale();
        $file = collect(["{$dir}/guide.{$locale}.md", "{$dir}/guide.md"])->first(fn (string $f) => is_file($f));

        if (!$file) {
            return null;
        }

        $meta = $this->parse($file)['meta'];

        if (empty($meta['title'])) {
            return null;
        }

        $updated = $meta['updated'] ?? null;

        return new Guide(
            slug: $slug,
            title: (string) $meta['title'],
            summary: isset($meta['summary']) ? (string) $meta['summary'] : null,
            category: (string) ($meta['category'] ?? __('guides.uncategorized')),
            order: (int) ($meta['order'] ?? 100),
            duration: isset($meta['duration']) ? (int) $meta['duration'] : null,
            icon: (string) ($meta['icon'] ?? 'fas fa-book-open'),
            can: array_values(array_filter((array) ($meta['can'] ?? []), 'is_string')),
            features: array_values(array_filter((array) ($meta['features'] ?? []), 'is_string')),
            // YAML lit une date AAAA-MM-JJ comme un horodatage.
            updated: is_int($updated) ? date('Y-m-d', $updated) : ($updated ? (string) $updated : null),
            file: $file,
        );
    }

    /**
     * @return array{meta: array<string, mixed>, content: string}
     */
    private function parse(string $file): array
    {
        $result = (new FrontMatterParser(new SymfonyYamlFrontMatterParser()))
            ->parse((string) file_get_contents($file));
        $meta = $result->getFrontMatter();

        return ['meta' => is_array($meta) ? $meta : [], 'content' => $result->getContent()];
    }

    private function allows(?User $user, Guide $guide): bool
    {
        if ($guide->can === []) {
            return true;
        }

        return $user !== null && collect($guide->can)->contains(fn (string $permission) => $user->can($permission));
    }

    private function featuresEnabled(Guide $guide): bool
    {
        return collect($guide->features)->every(fn (string $feature) => $this->featureEnabled($feature));
    }

    /**
     * Option de config('guides.features') ; un nom inconnu vaut « désactivée ».
     */
    public function featureEnabled(string $feature): bool
    {
        $key = config("guides.features.{$feature}");

        return is_string($key) && (bool) config($key);
    }
}
