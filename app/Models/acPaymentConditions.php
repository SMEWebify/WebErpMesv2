<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class acPaymentConditions extends Model
{
    use HasFactory;

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }
}
