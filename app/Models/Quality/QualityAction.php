<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityAction extends Model
{
    use HasFactory;

    protected $fillable = ['code',
                            'label', 
                            'statu',
                            'type', 
                            'user_id',
                            'pb_descp',  
                            'cause',  
                            'action', 
                            'color', 
                            'quality_non_conformitie_id'];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function QualityNonConformity()
    {
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformitie_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
