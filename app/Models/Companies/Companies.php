<?php

namespace App\Models\Companies;

use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Companies extends Model
{
    use HasFactory;

    protected $fillable = ['code', 
                            'label',
                            'website',
                            'fbsite',
                            'twittersite', 
                            'lkdsite', 
                            'siren', 
                            'naf_code', 
                            'intra_community_vat', 
                            'statu_customer',
                            'discount',
                            'user_id',
                            'account_general_customer',
                            'account_auxiliary_customer',
                            'statu_supplier',
                            'account_general_supplier',
                            'account_auxiliary_supplier',
                            'recept_controle',
                            'comment',
                            'active',
                        ];

    public function Addresses()
    {
        return $this->hasMany(CompaniesAddresses::class);
    }

    public function getAddressesCountAttribute()
    {
        return $this->Addresses()->count();
    }

    public function Contacts()
    {
        return $this->hasMany(CompaniesContacts::class);
    }

    public function geContactsCountAttribute()
    {
        return $this->Contacts()->count();
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Quotes()
    {
        return $this->hasMany(Quotes::class, 'companies_id');
    }

    public function NonConformity()
    {
        return $this->hasMany(QualityNonConformity::class, 'companies_id');
    }

    public function getQuotesCountAttribute()
    {
        return $this->Quotes()->count();
    }

    public function Orders()
    {
        return $this->hasMany(Orders::class, 'companies_id');
    }

    public function getOrdersCountAttribute()
    {
        return $this->Quotes()->count();
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
