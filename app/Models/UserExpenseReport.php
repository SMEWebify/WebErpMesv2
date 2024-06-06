<?php

namespace App\Models;

use App\Models\User;
use App\Models\UserExpense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserExpenseReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'label',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(UserExpense::class, 'report_id');
    }

    public function getTotalAmountAttribute()
    {
        return $this->expenses()->sum('amount');
    }
}
