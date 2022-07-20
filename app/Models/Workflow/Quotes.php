<?php

namespace App\Models\Workflow;

use App\Models\User;
use App\Services\QuoteCalculator;
use App\Models\Companies\Companies;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;

use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Quotes extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['code', 
                            'label', 
                            'customer_reference',
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'validity_date',  
                            'statu',  
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

    public function payment_condition()
    {
        return $this->belongsTo(AccountingPaymentConditions::class, 'accounting_payment_conditions_id');
    }

    public function payment_method()
    {
        return $this->belongsTo(AccountingPaymentMethod::class, 'accounting_payment_methods_id');
    }

    public function delevery_method()
    {
        return $this->belongsTo(AccountingDelivery::class, 'accounting_deliveries_id');
    }

    public function QuoteLines()
    {
        return $this->hasMany(QuoteLines::class)->orderBy('ordre');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getTotalPriceAttribute()
    {
        $quoteCalculator = new QuoteCalculator($this);
        return $quoteCalculator->getTotalPrice();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
