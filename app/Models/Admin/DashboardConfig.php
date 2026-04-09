<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class DashboardConfig extends Model
{
    protected $fillable = ['user_id', 'layout'];

    protected $casts = ['layout' => 'array'];
}
