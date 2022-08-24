<?php

namespace App\Models\Quality;

use App\Models\File;
use App\Models\User;
use App\Models\Companies\Companies;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityNonConformity extends Model
{
    use HasFactory;
    
    protected $fillable = ['code',
                        'label', 
                        'statu',
                        'type', 
                        'user_id',
                        'service_id',  
                        'failure_id',  
                        'failure_comment', 
                        'causes_id', 
                        'causes_comment',  
                        'correction_id',  
                        'correction_comment', 
                        'causes_comment',  
                        'companie_id'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Action()
    {
        return $this->hasMany(QualityAction::class);
    }

    public function Derogation()
    {
        return $this->hasMany(QualityDerogation::class);
    }

    public function Failure()
    {
        return $this->belongsTo(QualityFailure::class, 'failure_id');
    }

    public function Cause()
    {
        return $this->belongsTo(QualityCause::class, 'causes_id');
    }

    public function Correction()
    {
        return $this->belongsTo(QualityCorrection::class, 'correction_id');
    }

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companie_id');
    }

    public function Task()
    {
        return $this->hasMany(Task::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
