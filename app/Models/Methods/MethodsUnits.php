<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use App\Traits\HasDefaultTrait;
use App\Models\Products\Products;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsUnits extends Model
{
    use HasFactory; use HasDefaultTrait;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',  'label',  'type', 'default'];

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

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
    }
}
