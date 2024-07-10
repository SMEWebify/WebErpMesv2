<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\GuestVisits;
use App\Models\Workflow\Orders;
use App\Services\QuoteCalculatorService;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;

use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quotes extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['uuid',
                            'code', 
                            'label', 
                            'customer_reference',
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'validity_date',  
                            'statu',  
                            'user_id',  
                            'opportunities_id',  
                            'accounting_payment_conditions_id',  
                            'accounting_payment_methods_id',  
                            'accounting_deliveries_id',  
                            'comment', ];

    // Relationship with the company associated with the quote
    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

   // Relationship with the contact associated with the quote
    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    // Relationship with the adresse associated with the quote
    public function adresse()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }

        // Relationship with the user associated with the quote
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

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->hasMany(File::class);
    }
    
    // Relationship with the opportunities associated with the Quote
    public function opportunities()
    {
        return $this->belongsTo(Opportunities::class, 'opportunities_id');
    }

    // Relationship with the Orders associated with the Quote
    public function Orders()
    {
        return $this->hasMany(Orders::class);
    }

    public function GetshortCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    //Get Created attribute like '	06 December 2023'
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getTotalPriceAttribute()
    {
        $QuoteCalculatorService = new QuoteCalculatorService($this);
        return $QuoteCalculatorService->getTotalPrice();
    }

    public function guestVisits()
    {
        return $this->hasMany(GuestVisits::class);
    }

    public function visitsCount()
    {
        return $this->guestVisits()->count();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
