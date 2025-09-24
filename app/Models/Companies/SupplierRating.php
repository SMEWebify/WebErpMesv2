<?php

namespace App\Models\Companies;

use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class SupplierRating extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable = [
        'purchases_id',
        'companies_id',
        'rating',
        'comment',
        'approved_at',
        'next_review_at',
        'evaluation_status',
        'evaluation_score_quality',
        'evaluation_score_logistics',
        'evaluation_score_service',
        'action_plan',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'next_review_at' => 'date',
        'evaluation_score_quality' => 'integer',
        'evaluation_score_logistics' => 'integer',
        'evaluation_score_service' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(Purchases::class, 'purchases_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Companies::class);
    }

    public function getCompositeScoreAttribute(): ?float
    {
        $scores = Collection::make([
            $this->evaluation_score_quality,
            $this->evaluation_score_logistics,
            $this->evaluation_score_service,
        ])->filter(fn ($value) => $value !== null);

        if ($scores->isEmpty()) {
            return null;
        }

        return round($scores->avg(), 1);
    }
}
