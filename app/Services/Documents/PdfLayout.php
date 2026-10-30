<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Un modèle d'impression proposé pour un document (voir PdfLayoutRegistry).
 */
final class PdfLayout
{
    /** Données que le service sait préparer pour un modèle, à la demande du manifeste. */
    public const WITH_DETAILS   = 'details';    // description longue, caractéristiques, exigences
    public const WITH_PLANS     = 'plans';      // vignette du plan principal
    public const WITH_ESTIMATES = 'estimates';  // blocs « estimation de projet » cochés pour le PDF (devis)
    public const WITH_PACKAGES  = 'packages';   // colis, contenu, hors colis et totaux (BL, PackingList)

    /**
     * @param  string|array<string,string>|null  $label  clé de traduction, texte, ou textes par langue
     * @param  string|null  $view  null pour le modèle par défaut (vue de base, thème compris)
     * @param  list<string>  $with
     */
    public function __construct(
        public readonly string $key,
        public readonly ?string $view,
        public readonly bool $prices = true,
        public readonly array $with = [],
        private readonly string|array|null $label = null,
        public readonly int $order = 100,
    ) {}

    public function isDefault(): bool
    {
        return $this->view === null;
    }

    public function wants(string $data): bool
    {
        return in_array($data, $this->with, true);
    }

    public function label(): string
    {
        $label = $this->label;

        if (is_array($label)) {
            $label = $label[app()->getLocale()] ?? $label['en'] ?? reset($label) ?: null;
        }

        if (is_string($label) && $label !== '') {
            return Lang::has($label) ? __($label) : $label;
        }

        return Str::headline($this->key);
    }
}
