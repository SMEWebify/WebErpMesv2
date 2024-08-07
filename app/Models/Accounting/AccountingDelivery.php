<?php

namespace App\Models\Accounting;

use App\Traits\HasDefaultTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingDelivery extends Model
{
    use HasFactory; use HasDefaultTrait;

    protected $fillable = ['code',  'label',  'default'];
}
