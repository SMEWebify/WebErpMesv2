<?php

namespace App\Models\Products;

use App\Models\File;
use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Services\Files\FileRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'product_id',
        'production_date',
        'expiration_date',
        'closed_at',
        // Coulée et certificat matière (réception)
        'heat_number',
        'supplier_batch_code',
        'certificate_type',
        'companies_id',
        'purchase_receipt_line_id',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    // Ligne de réception d'origine : point de départ de la traçabilité montante
    public function purchaseReceiptLine()
    {
        return $this->belongsTo(PurchaseReceiptLines::class, 'purchase_receipt_line_id');
    }

    public function stockMoves()
    {
        return $this->hasMany(StockMove::class);
    }

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumbers::class);
    }

    // Documents du lot dans la GED (alias `batch`) : certificat matière, contrôle…
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    public function certificates()
    {
        return $this->files()->wherePivot('role', FileRole::CERTIFICATE);
    }

    /**
     * Recherche partielle sur le code du lot, la coulée et le lot fournisseur.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw("batches.heat_number LIKE ? ESCAPE '!'", [$like])
              ->orWhereRaw("batches.supplier_batch_code LIKE ? ESCAPE '!'", [$like])
              ->orWhereRaw("batches.code LIKE ? ESCAPE '!'", [$like]);
        });
    }
}
