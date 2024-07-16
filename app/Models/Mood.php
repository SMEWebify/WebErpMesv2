<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mood extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'mood', 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
