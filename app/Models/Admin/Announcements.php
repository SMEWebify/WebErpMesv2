<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcements extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'user_id', 'comment'];

    // Relationship with the user associated with the order
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
