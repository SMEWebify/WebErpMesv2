<?php

namespace App\Services\Quotes;

use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Services\Planning\BillOfMaterialsCopier;
use Illuminate\Support\Facades\DB;

/**
 * Copie profonde d'une ligne de devis, vers le même devis ou un autre :
 * détail technique, gamme et nomenclature complètes (voir
 * BillOfMaterialsCopier), ressources affectées et fichiers GED.
 */
class QuoteLineCopier
{
    public function __construct(private readonly BillOfMaterialsCopier $bom) {}

    /**
     * @param  array  $overrides  attributs de la nouvelle ligne (code, label…)
     */
    public function copy(QuoteLines $source, int $targetQuoteId, int $ordre, array $overrides = []): QuoteLines
    {
        return DB::transaction(function () use ($source, $targetQuoteId, $ordre, $overrides) {
            $newLine = $source->replicate();
            $newLine->fill($overrides);
            $newLine->quotes_id = $targetQuoteId;
            $newLine->ordre     = $ordre;
            $newLine->save();

            $details = QuoteLineDetails::where('quote_lines_id', $source->id)->first();
            if ($details) {
                $newDetails = $details->replicate();
                $newDetails->quote_lines_id = $newLine->id;
                $newDetails->save();
            } else {
                QuoteLineDetails::create(['quote_lines_id' => $newLine->id]);
            }

            $this->bom->copy('quote_lines_id', $source->id, 'quote_lines_id', $newLine->id, '5');

            $pivots = $source->files->mapWithKeys(fn ($file) => [
                $file->id => [
                    'role'       => $file->pivot->role,
                    'is_primary' => (bool) $file->pivot->is_primary,
                ],
            ])->all();

            if (!empty($pivots)) {
                $newLine->files()->attach($pivots);
            }

            return $newLine;
        });
    }
}
