<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LockedPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'month', 'locked_by', 'locked_at'];

    protected $casts = ['locked_at' => 'datetime'];

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function getLabel(): string
    {
        return str_pad($this->month, 2, '0', STR_PAD_LEFT) . '/' . $this->year;
    }
}
