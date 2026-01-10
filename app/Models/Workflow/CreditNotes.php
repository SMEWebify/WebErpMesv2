<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use Illuminate\Support\Number;
use App\Models\Workflow\Invoices;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Services\CreditNoteCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNotes extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= [
        'code', 
        'label', 
        'invoices_id', 
        'companies_id', 
        'companies_contacts_id', 
        'companies_addresses_id', 
        'statu', 
        'user_id', 
        'reason', 
        'validated_by', 
        'validated_at'
    ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'credit_note';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Credit note has been {$eventName}";
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class);
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

    public function creditNotelines()
    {
        return $this->hasMany(CreditNoteLines::class, 'credit_note_id');
    }

    public function getTotalPriceAttribute()
    {
        $creditNoteCalculator = new CreditNoteCalculatorService($this);
        return $creditNoteCalculator->getTotalPrice();
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
    
    // Relationship with the files associated with the CreditNotes
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([ 
                                                'code', 
                                                'label', 
                                                'invoices_id', 
                                                'companies_id', 
                                                'companies_contacts_id', 
                                                'companies_addresses_id', 
                                                'statu', 
                                                'user_id', 
                                                'reason', 
                                                'validated_by', 
                                                'validated_at']);
        // Chain fluent methods for configuration options
    }
}
