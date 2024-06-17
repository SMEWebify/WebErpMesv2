<?php

namespace App\Models\Companies;

use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\SupplierRating;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\Purchases;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Companies extends Model
{
    use HasFactory;

    protected $fillable = ['code', 
                            'client_type',
                            'civility',
                            'label',
                            'last_name',
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
                            'barcode_value',
                        ];

    public function getLabelAttribute()
    {
        if ($this->client_type == '2') { // If it is an individual
            return "{$this->civility} {$this->attributes['label']} {$this->last_name}";
        }

        // Otherwise, return the original label
        return $this->attributes['label'];
    }

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

    public function Leads()
    {
        return $this->hasMany(Leads::class, 'companies_id');
    }

    public function Quotes()
    {
        return $this->hasMany(Quotes::class, 'companies_id');
    }

    public function NonConformity()
    {
        return $this->hasMany(QualityNonConformity::class, 'companies_id');
    }

    public function getLeadsCountAttribute()
    {
        return $this->Leads()->count();
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
        return $this->Orders()->count();
    }

    public function Purchases()
    {
        return $this->hasMany(Purchases::class, 'companies_id');
    }

    public function getPurchasesCountAttribute()
    {
        return $this->Purchases()->count();
    }

    // Relationship with the files associated with the Companies
    public function files()
    {
        return $this->hasMany(File::class);
    }
    // Relationship with the Rating associated with the Companies
    public function rating()
    {
        return $this->hasMany(SupplierRating::class);
    }
    
    public function averageRating()
    {
        return $this->rating()->avg('rating');
    }

    public function productsfromThisSupplier() {
        return $this->belongsToMany(Products::class, 'products_preferred_suppliers', 'companies_id', 'product_id')->withTimestamps();
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
