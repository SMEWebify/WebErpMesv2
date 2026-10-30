<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityAction extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',
                            'label', 
                            'statu',
                            'type', 
                            'user_id',
                            'pb_descp',  
                            'cause',  
                            'action', 
                            'color', 
                            'quality_non_conformitie_id'];

    /**
     * Journal des modifications (ISO 19443) : seulement les champs modifiés,
     * jamais les horodatages.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'label', 'statu', 'type', 'user_id', 'pb_descp', 'cause', 'action',
                'color', 'quality_non_conformitie_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function QualityNonConformity()
    {
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformitie_id');
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
    }
}
