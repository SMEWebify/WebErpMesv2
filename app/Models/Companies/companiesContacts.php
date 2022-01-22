<?php

namespace App\Models\Companies;

use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompaniesContacts extends Model
{
    use HasFactory;

    protected $fillable = ['companies_id', 'ORDRE', 'CIVILITY', 'FIRST_NAME','NAME','FUNCTION','NUMBER','MOBILE','MAIL'];

    public $timestamps = false;

    public function companie()
    {
        return $this->belongsTo(Companies::class);
    }
}
