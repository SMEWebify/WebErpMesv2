<?php

namespace App\Models;

use App\Models\User;
use App\Models\UserExpense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserExpenseReport extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= [
        'user_id',
        'date',
        'label',
        'status'
    ];

    /**
     * Get the user that owns the expense report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expenses for the expense report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expenses()
    {
        return $this->hasMany(UserExpense::class, 'report_id');
    }

    /**
     * Get the total amount of all expenses in the report.
     *
     * @return float
     */
    public function getTotalAmountAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    // Receipts (photos, scans), served through the unified document manager
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
    }
}
