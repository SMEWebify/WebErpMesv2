<?php

namespace App\Models\Products;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    public const SCOPE_ALL      = 'all';
    public const SCOPE_LOCATION = 'location';
    public const SCOPE_CATEGORY = 'category';

    public const STATUS_DRAFT     = 1;
    public const STATUS_EXPORTED  = 2;
    public const STATUS_VALIDATED = 3;
    public const STATUS_CANCELLED = 4;

    protected $fillable = [
        'code',
        'label',
        'scope_type',
        'scope_ids',
        'start_date',
        'end_date',
        'frozen_at',
        'validated_at',
        'validated_by',
        'created_by',
        'file_id',
        'entry_move_id',
        'exit_move_id',
        'statu',
    ];

    protected $casts = [
        'scope_ids'    => 'array',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'frozen_at'    => 'datetime',
        'validated_at' => 'datetime',
        'statu'        => 'integer',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(InventoryDetail::class, 'inventory_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function entryMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class, 'entry_move_id');
    }

    public function exitMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class, 'exit_move_id');
    }

    public function isDraft(): bool
    {
        return $this->statu === self::STATUS_DRAFT;
    }

    public function isExported(): bool
    {
        return $this->statu === self::STATUS_EXPORTED;
    }

    public function isValidated(): bool
    {
        return $this->statu === self::STATUS_VALIDATED;
    }

    public function isCancelled(): bool
    {
        return $this->statu === self::STATUS_CANCELLED;
    }

    public function isLocked(): bool
    {
        return in_array($this->statu, [self::STATUS_VALIDATED, self::STATUS_CANCELLED], true);
    }

    public function getPrettyCreatedAttribute(): string
    {
        return $this->created_at?->format('d F Y') ?? '';
    }
}
