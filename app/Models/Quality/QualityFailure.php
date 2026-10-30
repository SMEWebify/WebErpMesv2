<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityFailure extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',  'label'];

    /**
     * Journal des modifications (ISO 19443) : seulement les champs modifiés,
     * jamais les horodatages.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'label'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function QualityNonConformity()
    {
        return $this->hasMany(QualityNonConformity::class);
    }
}
