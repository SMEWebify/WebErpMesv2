<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimatedBudgets extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'amount1', 'amount2', 'amount3','amount4','amount5','amount6','amount7','amount8','amount9','amount10','amount11','amount12'];
}
