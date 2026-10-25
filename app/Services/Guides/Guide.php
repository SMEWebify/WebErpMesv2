<?php

namespace App\Services\Guides;

/**
 * Métadonnées d'un guide, lues dans l'en-tête YAML de son fichier Markdown.
 */
final class Guide
{
    /**
     * @param  array<int, string>  $can  permissions dont une seule suffit ; vide = guide visible par tous
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $summary,
        public readonly string $category,
        public readonly int $order,
        public readonly ?int $duration,
        public readonly string $icon,
        public readonly array $can,
        public readonly ?string $updated,
        public readonly string $file,
    ) {
    }
}
