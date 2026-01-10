<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use Illuminate\Support\Number;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Workflow\InvoiceLines;
use Illuminate\Database\Eloquent\Model;
use App\Services\InvoiceCalculatorService;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoices extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['uuid',
                            'code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',
                            'invoice_type',
                            'accounting_status',
                            'user_id',
                            'bank_id',
                            'comment',
                            'order_id',
                            'payment_date',
                            'due_date',
                            'export_date',
                            'incoterm',
                            ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'invoice';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Invoice has been {$eventName}";
    }

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

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLines::class)->orderBy('ordre');
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
        $InvoiceCalculatorService = new InvoiceCalculatorService($this);
        return $InvoiceCalculatorService->getTotalPrice();
        
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
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->getTotalPriceAttribute(), $currency, config('app.locale'));

    }

    // Relationship with the files associated with the Invoices
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([
                                                'code', 
                                                'label', 
                                                'companies_id', 
                                                'companies_contacts_id',   
                                                'companies_addresses_id',  
                                                'statu',
                                                'invoice_type',
                                                'accounting_status',
                                                'user_id',
                                                'bank_id',
                                                'comment',
                                                'order_id',
                                                'payment_date',
                                                'due_date',
                                                'export_date',
                                                'incoterm',]);
        // Chain fluent methods for configuration options
    }
}
