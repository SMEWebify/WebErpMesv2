<?php

namespace App\Models\Companies;

use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class companiesAddresses extends Model
{
    use HasFactory;

    protected $fillable = ['companies_id', 'ORDRE', 'LABEL', 'ADRESS','ZIPCODE','CITY','COUNTRY','NUMBER','MAIL'];
    
    public $timestamps = false;

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }
}
