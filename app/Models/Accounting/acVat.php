<?php

namespace App\Models\Accounting;

use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class acVat extends Model
{
    use HasFactory;

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }
}
