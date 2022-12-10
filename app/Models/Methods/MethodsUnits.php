<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsUnits extends Model
{
    use HasFactory;

    protected $fillable = ['code',  'label',  'type', 'default'];

    public function Product()
    {
        return $this->hasMany(Products::class);
    }

    public function QuoteLines()
    {
        return $this->hasMany(QuoteLines::class);
    }

    public function Task()
    {
        return $this->hasMany(Task::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
