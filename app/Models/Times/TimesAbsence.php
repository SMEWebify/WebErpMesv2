<?php

namespace App\Models\Times;

use App\Models\HumanResources\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimesAbsence extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['user_id', 'leave_type_id', 'absence_type',  'absence_type_day',  'days_count', 'hours_count', 'statu',  'start_date',  'end_date', 'comment'];

    protected $casts = [
        'days_count' => 'decimal:2',
        'hours_count' => 'decimal:2',
    ];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
