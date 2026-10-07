<?php

namespace App\Services\Quotes;

use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteProjectEstimate;
use App\Models\Workflow\Quotes;
use App\Services\DocumentCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copie d'un devis entier — en-tête, lignes (avec gamme, nomenclature et
 * fichiers, via QuoteLineCopier), documents du devis et estimation projet.
 *
 * Sert à trois usages : dupliquer un devis, enregistrer un devis comme trame,
 * et démarrer un devis depuis une trame.
 */
class QuoteDuplicator
{
    /** Champs du circuit de revue : propres au devis d'origine, jamais recopiés. */
    private const RESET = [
        'reviewed_by'         => null,
        'reviewed_at'         => null,
        'review_decision'     => null,
        'change_requested_by' => null,
        'change_reason'       => null,
        'change_approved_at'  => null,
        'csv_file_name'       => null,
    ];

    public function __construct(
        private readonly QuoteLineCopier $lines,
        private readonly DocumentCodeGenerator $codes,
    ) {}

    /**
     * Nouveau devis (même client) à partir d'un devis ou d'une trame.
     */
    public function duplicate(Quotes $source, array $attributes = []): Quotes
    {
        return DB::transaction(function () use ($source, $attributes) {
            $quote = $source->replicate(['uuid', 'code', 'is_template']);
            $quote->fill(self::RESET + [
                'statu'         => 1,
                'validity_date' => $this->defaultValidityDate() ?? $source->validity_date,
                'user_id'       => auth()->id() ?? $source->user_id,
            ]);
            $quote->fill($attributes);
            $quote->uuid        = (string) Str::uuid();
            $quote->is_template = false;
            $quote->code        = $attributes['code'] ?? $this->nextQuoteCode();
            $quote->save();

            $this->copyContent($source, $quote, resetDates: (bool) $source->is_template);

            return $quote;
        });
    }

    /**
     * Trame à partir d'un devis : sans client réel à retenir, sans dates ni
     * référence client, codée hors de la numérotation des devis.
     */
    public function saveAsTemplate(Quotes $source, string $label): Quotes
    {
        return DB::transaction(function () use ($source, $label) {
            $template = $source->replicate(['uuid', 'code', 'is_template']);
            $template->fill(self::RESET + [
                'label'              => $label,
                'statu'              => 1,
                'customer_reference' => null,
                'validity_date'      => null,
                'opportunities_id'   => null,
                'user_id'            => auth()->id() ?? $source->user_id,
            ]);
            $template->uuid        = (string) Str::uuid();
            $template->is_template = true;
            $template->code        = 'TRAME-' . Str::upper(Str::random(8));
            $template->save();

            $template->code = 'TRAME-' . $template->id;
            $template->save();

            $this->copyContent($source, $template, resetDates: true);

            return $template;
        });
    }

    /**
     * Recopie dans un devis déjà créé (formulaire « Nouveau devis ») les
     * lignes, documents et estimation d'une trame.
     */
    public function fillFromTemplate(Quotes $template, Quotes $quote): void
    {
        DB::transaction(fn () => $this->copyContent($template, $quote, resetDates: true));
    }

    private function copyContent(Quotes $source, Quotes $target, bool $resetDates): void
    {
        $lineOverrides = ['statu' => 1];
        if ($resetDates) {
            // Une date de livraison n'a de sens que pour un devis donné.
            $lineOverrides['delivery_date'] = null;
        }

        $offset = (int) QuoteLines::where('quotes_id', $target->id)->max('ordre');

        foreach ($source->QuoteLines()->get() as $line) {
            $this->lines->copy($line, $target->id, $offset + (int) $line->ordre, $lineOverrides);
        }

        $pivots = $source->files()->get()->mapWithKeys(fn ($file) => [
            $file->id => [
                'role'       => $file->pivot->role,
                'is_primary' => (bool) $file->pivot->is_primary,
            ],
        ])->all();

        if (!empty($pivots)) {
            $target->files()->syncWithoutDetaching($pivots);
        }

        $estimate = QuoteProjectEstimate::where('quotes_id', $source->id)->first();
        if ($estimate && !QuoteProjectEstimate::where('quotes_id', $target->id)->exists()) {
            $copy = $estimate->replicate();
            $copy->quotes_id = $target->id;
            $copy->save();
        }
    }

    private function nextQuoteCode(): string
    {
        $lastId = Quotes::withTemplates()->withTrashed()->max('id') ?? 0;

        return $this->codes->generateDocumentCode('quote', (int) $lastId);
    }

    private function defaultValidityDate(): ?string
    {
        $days = (int) (app('Factory')->add_day_validity_quote ?? 0);

        return $days > 0 ? now()->addDays($days)->format('Y-m-d') : null;
    }
}
