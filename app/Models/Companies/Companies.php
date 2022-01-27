<?php

namespace App\Models\Companies;

use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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
                            'SIREN', 
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
                            'comment'
                        ];

    public function Addresses()
    {
        return $this->hasMany(CompaniesAddresses::class);
    }

    public function Contacts()
    {
        return $this->hasMany(CompaniesContacts::class);
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
