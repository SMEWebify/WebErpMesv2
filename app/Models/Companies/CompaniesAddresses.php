<?php

namespace App\Models\Companies;

use App\Traits\HasDefaultTrait;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompaniesAddresses extends Model
{
    use HasFactory, HasDefaultTrait;

    protected $fillable = ['companies_id', 'ordre', 'label', 'adress','zipcode','city','country','number','mail', 'default'];
    
    public $timestamps = false;

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }
}
