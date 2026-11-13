<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Session NEST2BEND ouverte depuis une ligne de devis.
 *
 * Créée au clic sur « Simuler » (pending), complétée quand l'ERP a relu le résultat
 * (done). Écrite seulement par App\Services\Integrations\Nest2Bend\BendingSimulationService.
 * L'URL d'iframe n'y est jamais stockée : son code est à usage unique.
 */
class BendingSimulation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE = 'done';

    // Fiche de réglage PDF (champ `fiche` du résultat).
    public const SHEET_PENDING = 'pending';
    public const SHEET_STORED = 'stored';
    public const SHEET_FAILED = 'failed';

    protected $fillable = [
        'quote_lines_id',
        'user_id',
        'file_id',
        'reference_externe',
        'session_id',
        'result_id',
        'status',
        'politique',
        'quantite',
        'preparation_s',
        'unitaire_s',
        'compteurs',
        'section',
        'completed_at',
        'fiche_url',
        'fiche_status',
        'fiche_file_id',
        'fiche_error',
        'simulation',
    ];

    protected $casts = [
        'compteurs'     => 'array',
        'section'       => 'array',
        'preparation_s' => 'float',
        'unitaire_s'    => 'float',
        'quantite'      => 'integer',
        'completed_at'  => 'datetime',
        'simulation'    => 'array',
        'fiche_file_id' => 'integer',
        'fiche_error'   => 'integer',
    ];

    public function quoteLine()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }

    public function sheetFile()
    {
        return $this->belongsTo(\App\Models\File::class, 'fiche_file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
