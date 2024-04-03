<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['label','user_id','related_id','related_type'];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }
}
