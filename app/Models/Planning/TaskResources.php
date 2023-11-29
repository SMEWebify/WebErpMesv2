<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskResources extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 
                            'methods_ressources_id',
                            'autoselected_ressource',
                            'userforced_ressource',];
}
