<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Imbrication réellement découpée : une ou plusieurs tôles (ou chutes) sorties du
 * stock, réparties sur les lignes de commande de ses pièces. Écrite seulement par
 * App\Services\Stock\NestRecorder. Une estimation NestEngine n'en est jamais une.
 */
class Nest extends Model
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_N2P = 'n2p';

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'source', 'external_id', 'name', 'product_id', 'material', 'thickness',
        'sheet_x', 'sheet_y', 'sheet_qty', 'usage_percent', 'machine', 'status',
        'cut_at', 'user_id', 'payload', 'comment',
    ];

    protected $casts = [
        'thickness'            => 'float',
        'sheet_x'              => 'float',
        'sheet_y'              => 'float',
        'sheet_qty'            => 'float',
        'usage_percent'        => 'float',
        'cut_at'               => 'datetime',
        'remnants_recorded_at' => 'datetime',
        'payload'              => 'array',
    ];

    public function parts(): HasMany
    {
        return $this->hasMany(NestPart::class);
    }

    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
