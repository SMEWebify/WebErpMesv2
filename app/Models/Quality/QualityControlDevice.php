<?php

namespace App\Models\Quality;

use App\Models\User;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityControlDevice extends Model
{
    use HasFactory;

    protected $fillable = ['code',  'label',  'service_id',  'user_id',  'serial_number',  'picture'];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
