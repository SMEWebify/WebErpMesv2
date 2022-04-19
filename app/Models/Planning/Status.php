<?php

namespace App\Models\Planning;

use App\Models\User;
use App\Models\Planning\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Status extends Model
{
    protected $fillable = ['title', 'order'];

    public $timestamps = false;

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('ordre')->whereNotNull('order_lines_id');
    }
}
