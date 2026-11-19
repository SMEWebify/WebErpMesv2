<?php

namespace App\Services;

use App\Models\Admin\Factory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

class PdfThemeResolver
{
    public function __construct(private ConfigRepository $config)
    {
    }

    public function resolveForDocument(object $document, ?string $baseViewKey = null, ?Factory $factory = null): string
    {
        $baseViewKey ??= $this->resolveBaseViewKeyFromDocument($document);

        return $this->resolve($baseViewKey, $factory);
    }

    public function resolve(string $baseViewKey, ?Factory $factory = null): string
    {
        $customView = $this->resolveCustomOverride($baseViewKey);
        if ($customView) {
            return $customView;
        }

        $theme = $this->determineTheme($factory);
        $themes = $this->config->get('pdf.themes', []);

        if ($theme && isset($themes[$theme][$baseViewKey])) {
            return $themes[$theme][$baseViewKey];
        }

        $fallbackTheme = $this->config->get('pdf.fallback_theme');
        if ($fallbackTheme && isset($themes[$fallbackTheme][$baseViewKey])) {
            return $themes[$fallbackTheme][$baseViewKey];
        }

        return $baseViewKey;
    }

    private function resolveCustomOverride(string $baseViewKey): ?string
    {
        return $this->customView($baseViewKey);
    }

    /**
     * Vue personnalisée (non versionnée) d'une vue de base : print/custom/{client}/
     * quand pdf.custom_client est renseigné, puis print/custom/ (déploiements d'avant).
     */
    public function customView(string $baseViewKey): ?string
    {
        $parts = explode('/', $baseViewKey);
        $filename = array_pop($parts);
        $customRoot = implode('/', $parts) . '/custom/';

        $candidates = [$customRoot . $filename];
        if ($client = self::customClient()) {
            array_unshift($candidates, $customRoot . $client . '/' . $filename);
        }

        foreach ($candidates as $customViewKey) {
            if (View::exists($customViewKey)) {
                return $customViewKey;
            }
        }

        return null;
    }

    /**
     * Dossier client des vues personnalisées (PDF_CUSTOM_CLIENT), null si vide ou
     * si le nom n'est pas un simple nom de dossier.
     */
    public static function customClient(): ?string
    {
        $client = trim((string) config('pdf.custom_client'));

        return preg_match('/^[A-Za-z0-9_-]+$/', $client) ? $client : null;
    }

    public function determineTheme(?Factory $factory = null): string
    {
        $factory ??= App::has('Factory') ? App::get('Factory') : null;

        return $factory?->pdf_theme ?: $this->config->get('pdf.fallback_theme', 'default');
    }

    private function resolveBaseViewKeyFromDocument(object $document): string
    {
        $documents = $this->config->get('pdf.documents', []);
        $class = $document::class;

        if (isset($documents[$class])) {
            return $documents[$class];
        }

        throw new InvalidArgumentException(sprintf('No PDF template configured for document class [%s]', $class));
    }
}
