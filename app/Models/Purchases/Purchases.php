<?php

namespace App\Models\Purchases;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use Illuminate\Support\Number;
use App\Models\Workflow\Deliverys;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\SupplierRating;
use App\Models\Companies\CompaniesContacts;
use App\Services\PurchaseCalculatorService;
use App\Services\Purchases\PurchaseQualityClauses;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchases extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['code', 
                            'label', 
                            'companies_id', 
                            'companies_contacts_id',   
                            'companies_addresses_id',  
                            'statu',  
                            'user_id',
                            'comment',
                            'csv_file_name',
                        ];

    protected $casts = [
        'quality_clauses_frozen_at' => 'datetime',
        'ordered_at'                => 'datetime',
    ];

    /**
     * Clauses qualité figées à la sortie de « en cours », effacées au retour
     * (App\Services\Purchases\PurchaseQualityClauses).
     */
    protected static function booted(): void
    {
        // Passage de « en cours » à commandée : point de départ de l'attente de l'AR
        // fournisseur (App\Services\Purchases\SupplierAcknowledgement). Retour en cours = effacé.
        static::creating(function (Purchases $purchase) {
            if ($purchase->ordered_at === null && ! in_array((int) ($purchase->statu ?? 1), [1, 5], true)) {
                $purchase->ordered_at = now();
            }
        });

        static::updating(function (Purchases $purchase) {
            if (! $purchase->isDirty('statu')) {
                return;
            }

            $statu = (int) $purchase->statu;
            if ($statu === 1) {
                $purchase->ordered_at = null;
            } elseif ((int) $purchase->getOriginal('statu') === 1 && $statu !== 5) {
                $purchase->ordered_at = now();
            }
        });

        static::updated(function (Purchases $purchase) {
            if (! $purchase->wasChanged('statu')) {
                return;
            }

            $clauses = app(PurchaseQualityClauses::class);

            if ((int) $purchase->statu === 1) {
                $clauses->unfreeze($purchase);
            } elseif ($purchase->quality_clauses_frozen_at === null) {
                $clauses->freeze($purchase);
            }
        });
    }

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'purchase';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Purchase has been {$eventName}";
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

    public function PurchaseLines()
    {
        return $this->hasMany(PurchaseLines::class)->orderBy('ordre');
    }

    // Copie figée des clauses qualité (lire par PurchaseQualityClauses::forOrder())
    public function qualityClauses()
    {
        return $this->hasMany(PurchaseOrderQualityClause::class)->orderBy('ordre');
    }

    // Relationship with the files associated with the Purchases
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
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

    // Relationship with the Rating associated with the Purchases
    // (évaluation de la commande : les notes de réception ont leur propre lien)
    public function Rating()
    {
        return $this->hasMany(SupplierRating::class)->whereNull('purchase_receipt_id');
    }
        
    // Relationship with the deliveries associated with the Purchases
    public function deliveries()
    {
        return $this->hasMany(Deliverys::class);
    }

    public function getTotalPriceAttribute()
    {
        $PurchaseCalculatorService = new PurchaseCalculatorService($this);
        return $PurchaseCalculatorService->getTotalPrice();
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
        return LogOptions::defaults()->logOnly([ 'code', 
                                                'label', 
                                                'companies_id', 
                                                'companies_contacts_id',   
                                                'companies_addresses_id',  
                                                'statu',  
                                                'user_id',
                                                'comment',
                                                'csv_file_name',]);
        // Chain fluent methods for configuration options
    }
}
