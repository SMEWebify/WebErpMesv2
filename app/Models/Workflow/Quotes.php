<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\GuestVisits;
use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use Spatie\Activitylog\LogOptions;

use App\Models\Companies\Companies;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Services\QuoteCalculatorService;
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

    // Fillable attributes for mass assignment
    protected $fillable= ['uuid',
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
                            'comment',
                            'csv_file_name',
                            'reviewed_by',
                            'reviewed_at',
                            'review_decision',
                            'change_requested_by',
                            'change_reason',
                            'change_approved_at',];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'change_approved_at' => 'datetime',
    ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'quote';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Quote has been {$eventName}";
    }

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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function changeRequester()
    {
        return $this->belongsTo(User::class, 'change_requested_by');
    }

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    /**
     * Get all of the email logs for the model.
     *
     * This function defines a polymorphic one-to-many relationship
     * between the model model and the EmailLog model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function emailLogs()
    {
        return $this->morphMany(EmailLog::class, 'emailable');
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

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getTotalPriceAttribute()
    {
        $QuoteCalculatorService = new QuoteCalculatorService($this);
        return $QuoteCalculatorService->getTotalPrice();
    }

    /**
     * Get the formatted total price attribute.
     *
     * This method retrieves the total price attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted total price.
     */
    public function getFormattedTotalPriceAttribute()
    {
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->getTotalPriceAttribute(), $currency, config('app.locale'));

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
        return LogOptions::defaults()->logOnly(['code',
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
                                                'comment',
                                                'reviewed_by',
                                                'reviewed_at',
                                                'review_decision',
                                                'change_requested_by',
                                                'change_reason',
                                                'change_approved_at', ]);
        // Chain fluent methods for configuration options
    }
}
