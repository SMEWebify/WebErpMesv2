<?php

namespace App\Models\Companies;

use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompaniesContacts extends Model
{
    use HasFactory;

    protected $fillable = ['companies_id', 'ordre', 'civility', 'first_name','name','function','number','mobile','mail'];

    public $timestamps = false;

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchases::class);
    }
}
