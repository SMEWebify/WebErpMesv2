<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsTools extends Model
{
    use HasFactory;

    protected $fillable = ['code',  'label',  'ETAT', 'cost' , 'picture',  'end_date',  'comment',  'qty'];

    public function Task()
    {
        return $this->hasMany(Task::class);
    }
}
