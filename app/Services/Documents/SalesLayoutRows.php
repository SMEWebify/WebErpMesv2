<?php

namespace App\Services\Documents;

use App\Models\Workflow\QuoteProjectEstimate;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Number;

/**
 * Lignes d'un devis, d'une commande ou d'un AR préparées pour un modèle
 * d'impression autre que le modèle par défaut (voir PdfLayoutRegistry).
 *
 * Les rangées sont des tableaux, pas des modèles : un modèle sans prix ne reçoit
 * ainsi aucun montant, ni dans les rangées ni par une relation à parcourir.
 * Sections, textes et forfaits suivent SalesPrintLayout ; les sous-totaux, qui
 * ne portent qu'un montant, disparaissent d'un modèle sans prix.
 */
class SalesLayoutRows
{
    /**
     * Blocs de l'estimation de projet : [texte(s), case « PDF », coût, titre].
     * La case vaut 1 quand elle est cochée, 2 sinon (QuotesController::saveProjectEstimate).
     */
    private const ESTIMATE_BLOCKS = [
        [['client_requirements'], 'show_client_requirements_on_pdf', null, 'general_content.client_requirement_trans_key'],
        [['layout_plan', 'layout_improvements'], 'show_layout_on_pdf', null, 'general_content.layout_plan_trans_key'],
        [['materials_finishes'], 'show_materials_on_pdf', null, 'general_content.materials_finishes_trans_key'],
        [['logistics'], 'show_logistics_on_pdf', 'logistics_cost', 'general_content.logistics_trans_key'],
        [['coordination_with_contractors'], 'show_contractors_on_pdf', 'contractors_cost', 'general_content.coordination_with_contractors_trans_key'],
        [['waste_management'], 'show_waste_on_pdf', 'waste_management_cost', 'general_content.waste_management_trans_key'],
        [['taxes_and_fees'], 'show_taxes_on_pdf', 'taxes_cost', 'general_content.taxes_and_fees_trans_key'],
        [['options_variants'], 'show_options_on_pdf', null, 'pdf_layouts.estimate_options'],
        [['insurance_liability'], 'show_insurance_on_pdf', 'insurance_cost', 'pdf_layouts.estimate_insurance'],
        [['revision_clause'], 'show_revision_clause_on_pdf', null, 'pdf_layouts.estimate_revision_clause'],
        [['warranty_clause'], 'show_warranty_clause_on_pdf', null, 'pdf_layouts.estimate_warranty_clause'],
        [['professional_presentation'], 'show_presentation_on_pdf', null, 'pdf_layouts.estimate_presentation'],
    ];

    public function __construct(
        private readonly SalesPrintLayout $salesPrintLayout,
        private readonly PlanThumbnail $planThumbnail,
    ) {}

    /**
     * @param  iterable  $lines  lignes du document, toutes (présentation comprise)
     * @return list<array<string, mixed>>
     */
    public function rows(iterable $lines, PdfLayout $layout): array
    {
        $lines = collect($lines)->values();
        $rows  = $this->salesPrintLayout->build($lines)['rows']
            ?? $lines->map(fn ($line) => ['type' => 'article', 'line' => $line, 'label' => $line->label, 'amount' => $this->salesPrintLayout->lineNet($line), 'show_qty' => true])->all();

        $out = [];
        foreach ($rows as $row) {
            $prepared = match ($row['type']) {
                'article', 'package' => $this->article($row, $layout),
                'subtotal'           => $layout->prices ? ['type' => 'subtotal', 'label' => $row['label'], 'amount' => $this->money($row['amount'])] : null,
                default              => ['type' => $row['type'], 'label' => $row['label']],
            };
            if ($prepared !== null) {
                $out[] = $prepared;
            }
        }

        return $out;
    }

    /**
     * Blocs de l'estimation de projet cochés pour le PDF (devis uniquement).
     *
     * @return list<array{title: string, text: string, cost: ?string}>
     */
    public function estimates(object $document, PdfLayout $layout): array
    {
        if (!$document instanceof Quotes || !$layout->wants(PdfLayout::WITH_ESTIMATES)) {
            return [];
        }

        $estimate = QuoteProjectEstimate::where('quotes_id', $document->id)->first();
        if (!$estimate) {
            return [];
        }

        $blocks = [];
        foreach (self::ESTIMATE_BLOCKS as [$fields, $flag, $costField, $title]) {
            $text = trim(implode("\n\n", array_filter(array_map(fn ($f) => trim((string) $estimate->{$f}), $fields))));
            if ((int) $estimate->{$flag} !== 1 || $text === '') {
                continue;
            }

            $cost     = $costField ? $estimate->{$costField} : null;
            $blocks[] = [
                'title' => __($title),
                'text'  => $text,
                'cost'  => $layout->prices && $cost !== null ? $this->money($cost) : null,
            ];
        }

        return $blocks;
    }

    private function article(array $row, PdfLayout $layout): array
    {
        $line    = $row['line'];
        $details = $layout->wants(PdfLayout::WITH_DETAILS) ? $this->details($line) : null;
        $package = $row['type'] === 'package';

        $prepared = [
            'type'             => $row['type'],
            'label'            => $line->label,
            'code'             => $line->code,
            'ind'              => $line->ind,
            'equipment_tags'   => method_exists($line, 'equipmentTagsText') && !empty($line->equipment_tag) ? $line->equipmentTagsText() : null,
            'qty'              => $package ? ($row['show_qty'] ? 1 : null) : $line->qty,
            'unit'             => $package && !$row['show_qty'] ? null : ($line->unit_label ?? optional($line->Unit)['label']),
            'delivery_date'    => $line->delivery_date ?: null,
            'long_description' => filled($details?->external_comment) ? (string) $details->external_comment : null,
            'characteristics'  => $details ? $this->characteristics($details) : [],
            'requirements'     => $details ? $this->requirements($details) : [],
            'plan'             => $layout->wants(PdfLayout::WITH_PLANS) && !$package ? $this->planThumbnail->forLine($line) : null,
        ];

        if ($layout->prices) {
            $prepared += [
                'unit_price' => $this->normalize($line->formatted_selling_price),
                'discount'   => $package ? null : $line->discount,
                'amount'     => $this->money($row['amount'] ?? $this->salesPrintLayout->lineNet($line)),
            ];
        }

        return $prepared;
    }

    /**
     * Détails techniques : ligne de devis / commande, ou ligne de commande d'une ligne d'AR.
     */
    private function details(object $line): ?object
    {
        if (method_exists($line, 'QuoteLineDetails')) {
            return $line->QuoteLineDetails;
        }
        if (method_exists($line, 'OrderLineDetails')) {
            return $line->OrderLineDetails;
        }

        return $line->OrderLine?->OrderLineDetails;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function characteristics(object $details): array
    {
        $dimensions = array_filter([$details->x_size, $details->y_size, $details->z_size], fn ($v) => filled($v) && (float) $v != 0);

        $items = [
            'general_content.material_trans_key'      => $details->material,
            'general_content.thickness_trans_key'     => $this->number($details->thickness),
            'general_content.dimensions_xyz_trans_key' => $dimensions ? implode(' × ', array_map([$this, 'number'], $dimensions)) : null,
            'general_content.diameter_trans_key'      => $this->number($details->diameter),
            'general_content.finishing_trans_key'     => $details->finishing,
            'general_content.weight_trans_key'        => $this->number($details->weight),
            'general_content.bend_count_trans_key'    => $this->number($details->bend_count),
        ];

        $out = [];
        foreach ($items as $key => $value) {
            if (filled($value)) {
                $out[] = ['label' => __($key), 'value' => (string) $value];
            }
        }

        return $out;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function requirements(object $details): array
    {
        return collect($details->custom_requirements ?? [])
            ->filter(fn ($r) => is_array($r) && (filled($r['label'] ?? null) || filled($r['value'] ?? null)))
            ->map(fn ($r) => ['label' => (string) ($r['label'] ?? ''), 'value' => (string) ($r['value'] ?? '')])
            ->values()
            ->all();
    }

    /** Valeur numérique sans zéros inutiles ; null si vide ou nulle. */
    private function number($value): ?string
    {
        if (blank($value) || !is_numeric($value) || (float) $value == 0) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    }

    private function money($value): string
    {
        return $this->normalize(Number::currency((float) $value, app('Factory')->curency ?? 'EUR', config('app.locale')));
    }

    private function normalize($value): string
    {
        return str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);
    }
}
