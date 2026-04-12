<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class DashboardConfig extends Model
{
    protected $fillable = ['user_id', 'layout', 'today_layout', 'home_view'];

    protected $casts = [
        'layout'       => 'array',
        'today_layout' => 'array',
    ];
}
