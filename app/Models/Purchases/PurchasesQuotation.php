<?php

namespace App\Models\Purchases;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\CompaniesContacts;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchaseRfqGroup;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchasesQuotation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'rfq_group_id',
                            // Rattachement à l'affaire hors tâche (PurchaseAffairLink)
                            'opportunities_id',
                            'quotes_id',
                            'orders_id',
                            'statu',
                            'user_id',
                            'comment',
                            // Offre reçue du fournisseur (PurchaseOfferRevisions)
                            'supplier_offer_reference',
                            'supplier_offer_revision',
                            'supplier_offer_date',
                            'supplier_offer_validity',
                        ];

    // parent_purchase_quotation_id et superseded_at : écrits par PurchaseOfferRevisions seulement
    protected $casts = [
        'supplier_offer_date'     => 'date',
        'supplier_offer_validity' => 'date',
        'superseded_at'           => 'datetime',
    ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'purchase_quotation';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Purchase quotation has been {$eventName}";
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

    public function PurchaseQuotationLines()
    {
        return $this->hasMany(PurchaseQuotationLines::class)->orderBy('ordre');
    }

    public function rfqGroup()
    {
        return $this->belongsTo(PurchaseRfqGroup::class, 'rfq_group_id');
    }

    // Offre PDF du fournisseur et autres pièces (GED, alias purchase-quotation)
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /** Remplacée par un indice suivant de l'offre : ni commande ni modification. */
    public function isSuperseded(): bool
    {
        return $this->superseded_at !== null;
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
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
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
        return LogOptions::defaults()->logOnly([ 'code', 'label', 'statu',
                                                'supplier_offer_reference', 'supplier_offer_revision',
                                                'supplier_offer_date', 'supplier_offer_validity', 'superseded_at']);
        // Chain fluent methods for configuration options
    }
}
