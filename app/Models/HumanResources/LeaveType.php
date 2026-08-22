<?php

namespace App\Models\HumanResources;

use App\Models\Times\TimesAbsence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'color',
        'counts_against_balance',
        'default_annual_quota',
        'ordre',
        'active',
    ];

    protected $casts = [
        'counts_against_balance' => 'boolean',
        'active' => 'boolean',
        'default_annual_quota' => 'decimal:2',
    ];

    public function absences()
    {
        return $this->hasMany(TimesAbsence::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
