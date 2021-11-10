<?php

namespace App\Models\Workflow;

use App\Models\User;
use App\Models\Companies\Companies;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quotes extends Model
{
    use HasFactory;

    protected $fillable = ['CODE', 
                            'LABEL', 
                            'customer_reference',
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'validity_date',  
                            'STATU',  
                            'user_id',  
                            'accounting_payment_conditions_id',  
                            'accounting_payment_methods_id',  
                            'accounting_deliveries_id',  
                            'comment' ];

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function contact()
    {
        return $this->belongsTo(companiesContacts::class, 'companies_contacts_id');
    }

    public function adresse()
    {
        return $this->belongsTo(companiesAddresses::class, 'companies_addresses_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payment_condition()
    {
        return $this->belongsTo(AccountingPaymentConditions::class, 'accounting_payment_conditions_id');
    }

    public function payment_method()
    {
        return $this->belongsTo(AccountingPaymentMethod::class, 'accounting_payment_methods_id');
    }

    public function delevery()
    {
        return $this->belongsTo(AccountingDelivery::class, 'accounting_deliveries_id');
    }

    public function QuoteLines()
    {
        return $this->hasMany(QuoteLines::class)->orderBy('ORDRE');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

}
