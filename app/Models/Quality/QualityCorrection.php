<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class QualityCorrection extends Model
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
}
