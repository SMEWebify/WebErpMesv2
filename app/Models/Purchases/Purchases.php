<?php

namespace App\Models\Purchases;

use App\Models\File;
use App\Models\User;
use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\SupplierRating;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchases extends Model
{
    use HasFactory;

    protected $fillable = ['code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',  
                            'user_id',
                            'comment',
                        ];

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    public function adresse()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function PurchaseLines()
    {
        return $this->hasMany(PurchaseLines::class)->orderBy('ordre');
    }

    // Relationship with the files associated with the Purchases
    public function files()
    {
        return $this->hasMany(File::class);
    }

    // Relationship with the Rating associated with the Purchases
    public function Rating()
    {
        return $this->hasMany(SupplierRating::class);
    }
        

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
