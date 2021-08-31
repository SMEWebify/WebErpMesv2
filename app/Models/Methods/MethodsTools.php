<?php

namespace App\Models\Methods;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MethodsTools extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'ETAT', 'COST' , 'PICTURE',  'END_DATE',  'COMMENT',  'QTY'];
}
