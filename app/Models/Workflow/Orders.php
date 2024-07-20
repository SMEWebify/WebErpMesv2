<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Quotes;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\OrderRating;
use Illuminate\Database\Eloquent\Model;
use App\Services\OrderCalculatorService;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Orders extends Model
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
                            'accounting_payment_conditions_id',  
                            'accounting_payment_methods_id',  
                            'accounting_deliveries_id',  
                            'comment',
                            'quotes_id',
                            'type',
                        ];

    // Relationship with the company associated with the order
    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

   // Relationship with the contact associated with the order
    public function contact()
    {
        return $this->belongsTo(CompaniesContacts::class, 'companies_contacts_id');
    }

    // Relationship with the adresse associated with the order
    public function adresse()
    {
        return $this->belongsTo(CompaniesAddresses::class, 'companies_addresses_id');
    }

    // Relationship with the user associated with the order
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

    public function Quote()
    {
        return $this->belongsTo(Quotes::class, 'quotes_id');
    }

    public function OrderLines()
    {
        return $this->hasMany(OrderLines::class)->orderBy('ordre');
    }

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->hasMany(File::class);
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
        $OrderCalculatorService = new OrderCalculatorService($this);
        return $OrderCalculatorService->getTotalPrice();
    }

    public function getAveragePercentProgressLinesAttribute()
    {
        $SumPercent = $this->OrderLines->reduce(function ($SumPercentLine, $OrderLine) {
            if($OrderLine->getAveragePercentProgressTaskAttribute() > 100) $OrderLinePerCent = 100;
            else  $OrderLinePerCent = $OrderLine->getAveragePercentProgressTaskAttribute();

            return $SumPercentLine + $OrderLinePerCent;
            },0);

        $TotalCountLines = $this->OrderLines()->count();
        if($TotalCountLines <= 0 ) $TotalCountLines = 1;

        return $SumPercent/$TotalCountLines;
    }

    // Relationship with the Rating associated with the Purchases
    public function Rating()
    {
        return $this->hasMany(OrderRating::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
