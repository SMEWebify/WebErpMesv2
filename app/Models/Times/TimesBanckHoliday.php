<?php

namespace App\Models\Times;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimesBanckHoliday extends Model
{
    use HasFactory;

    protected $fillable = ['fixed',  'date',  'label'];
}
