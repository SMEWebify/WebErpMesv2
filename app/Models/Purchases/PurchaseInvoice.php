<?php

namespace App\Models\Purchases;

use App\Models\User;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchaseInvoiceLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseInvoice extends Model
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

    public function PurchaseInvoiceLines()
    {
        return $this->hasMany(PurchaseInvoiceLines::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
