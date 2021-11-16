<?php

namespace App\Models\Quality;

use App\Models\User;
use App\Models\Quality\QualityAction;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityDerogation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityNonConformity extends Model
{
    use HasFactory;
    
    protected $fillable = ['CODE',
                        'LABEL', 
                        'statu',
                        'TYPE', 
                        'user_id',
                        'service_id',  
                        'failure_id',  
                        'failure_COMMENT', 
                        'causes_id', 
                        'causes_COMMENT',  
                        'correction_id',  
                        'correction_COMMENT', 
                        'causes_COMMENT',  
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
        return $this->hasMany(QualityAction::class, 'action_id');
    }

    public function Derogation()
    {
        return $this->hasMany(QualityDerogation::class, 'derogation_id');
    }

    public function Task()
    {
        return $this->hasMany(Task::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
