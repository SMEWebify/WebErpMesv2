<?php

namespace App\Models;

use App\Models\UserExpense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'description'];

    public function expenses()
    {
        return $this->hasMany(UserExpense::class, 'category_id');
    }
}
