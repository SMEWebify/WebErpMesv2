<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\Log;

/**
 * Modèles d'impression d'un document : le modèle par défaut (« short », la vue
 * de base telle qu'elle s'imprime aujourd'hui) puis un modèle par dossier.
 *
 * Un dossier reprend les noms de fichier de print/ : print/layouts/long/pdf-sales.blade.php
 * est le modèle « long » du devis, de la commande et de l'AR. Un modèle n'est
 * donc proposé que pour les documents dont il contient la vue. Deux racines :
 * - print/layouts/{modèle}         versionnée ;
 * - print/custom/layouts/{modèle}  propre au client (non versionnée), qui
 *   remplace un fichier du même nom ou ajoute un modèle.
 *
 * Le manifeste layout.json (le plus proche : custom d'abord) est facultatif :
 *   {"label": "pdf_layouts.long" | {"fr": "…", "en": "…"}, "prices": true,
 *    "with": ["details", "plans", "estimates"], "order": 10}
 *
 * Un modèle « prices": false » ne reçoit aucune donnée de prix : le service lui
 * prépare des lignes sans montant. Ce n'est possible que pour les documents
 * dont on sait préparer ces lignes (PRICELESS) ; ailleurs le modèle n'est pas
 * proposé, pour qu'une vue ne reçoive jamais des prix qu'elle croit absents.
 *
 * La valeur reçue d'un formulaire n'est jamais assemblée en chemin : elle doit
 * correspondre à un modèle de cette liste.
 */
class PdfLayoutRegistry
{
    public const DEFAULT = 'short';

    /** Vues de base dont les lignes savent s'imprimer sans prix (SalesLayoutRows). */
    public const PRICELESS = ['print/pdf-sales'];

    private const NAME = '/^[a-z0-9][a-z0-9_-]{0,39}$/';

    /** @var array<string, array<string, PdfLayout>> */
    private array $cache = [];

    /**
     * @return array<string, PdfLayout> indexé par clé, modèle par défaut en premier
     */
    public function available(string $baseViewKey): array
    {
        return $this->cache[$baseViewKey] ??= $this->discover($baseViewKey);
    }

    public function find(string $baseViewKey, ?string $key): ?PdfLayout
    {
        return $this->available($baseViewKey)[$key ?: self::DEFAULT] ?? null;
    }

    /**
     * @return array<string, string> code => nom de la langue
     */
    public function languages(): array
    {
        return config('pdf.languages', []);
    }

    public function supportsLanguage(?string $locale): bool
    {
        return $locale === null || $locale === '' || array_key_exists($locale, $this->languages());
    }

    /**
     * @return array<string, PdfLayout>
     */
    private function discover(string $baseViewKey): array
    {
        $file    = basename($baseViewKey);
        $layouts = [self::DEFAULT => new PdfLayout(self::DEFAULT, null, label: 'pdf_layouts.short', order: 0)];
        $roots   = [
            'custom'   => config('pdf.custom_layouts_path', 'print/custom/layouts'),
            'standard' => config('pdf.layouts_path', 'print/layouts'),
        ];

        foreach ($this->folderNames($roots) as $key) {
            $view = $this->firstExisting($roots, $key, $file . '.blade.php');
            if ($view === null) {
                continue; // ce modèle ne couvre pas ce document
            }

            $manifest = $this->manifest($roots, $key);
            $prices   = (bool) ($manifest['prices'] ?? true);

            if (!$prices && !in_array($baseViewKey, self::PRICELESS, true)) {
                Log::warning("Modèle d'impression « {$key} » sans prix ignoré pour {$baseViewKey} : ce document ne sait pas encore s'imprimer sans prix.");
                continue;
            }

            $layouts[$key] = new PdfLayout(
                key: $key,
                view: $view,
                prices: $prices,
                with: array_values(array_filter((array) ($manifest['with'] ?? []), 'is_string')),
                label: $manifest['label'] ?? null,
                order: (int) ($manifest['order'] ?? 100),
            );
        }

        uasort($layouts, fn (PdfLayout $a, PdfLayout $b) => [$a->order, $a->key] <=> [$b->order, $b->key]);

        return $layouts;
    }

    /**
     * @param  array<string, string>  $roots
     * @return list<string>
     */
    private function folderNames(array $roots): array
    {
        $names = [];
        foreach ($roots as $root) {
            foreach (glob($this->path($root) . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $name = basename($dir);
                if ($name !== self::DEFAULT && preg_match(self::NAME, $name)) {
                    $names[$name] = true;
                }
            }
        }

        return array_keys($names);
    }

    /**
     * Clé de vue du fichier, dans la première racine qui le contient (custom d'abord).
     */
    private function firstExisting(array $roots, string $key, string $fileName): ?string
    {
        foreach ($roots as $root) {
            if (is_file($this->path($root) . "/{$key}/{$fileName}")) {
                return "{$root}/{$key}/" . substr($fileName, 0, -strlen('.blade.php'));
            }
        }

        return null;
    }

    private function manifest(array $roots, string $key): array
    {
        foreach ($roots as $root) {
            $path = $this->path($root) . "/{$key}/layout.json";
            if (!is_file($path)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($path), true);
            if (is_array($data)) {
                return $data;
            }

            Log::warning("Manifeste de modèle d'impression illisible : {$path}");
        }

        return [];
    }

    private function path(string $root): string
    {
        return resource_path('views/' . trim($root, '/'));
    }
}
