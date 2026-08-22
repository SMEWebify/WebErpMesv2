<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Services\OrderConfirmationCalculatorService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ARC — Accusé de Réception de Commande.
 *
 * Photo contractuelle de la commande au moment de la revue. Les lignes portent
 * leurs propres valeurs : ce modèle ne doit jamais relire la commande pour
 * afficher ou calculer quoi que ce soit.
 */
class OrderConfirmations extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_SENT        = 2;
    public const STATUS_ACCEPTED    = 3;
    public const STATUS_SUPERSEDED  = 4;

    // Fillable attributes for mass assignment
    protected $fillable = ['uuid',
                            'code',
                            'label',
                            'customer_reference',
                            'order_id',
                            'revision',
                            'statu',
                            'is_current',
                            'supersedes_id',
                            'companies_id',
                            'companies_contacts_id',
                            'companies_addresses_id',
                            'accounting_payment_conditions_id',
                            'accounting_payment_methods_id',
                            'accounting_deliveries_id',
                            'validity_date',
                            'user_id',
                            'comment',
                            'issued_at',
                            'sent_at',
                            'customer_accepted_at',
                        ];

    protected $casts = [
        'is_current'           => 'boolean',
        'validity_date'        => 'date',
        'issued_at'            => 'datetime',
        'sent_at'              => 'datetime',
        'customer_accepted_at' => 'datetime',
    ];

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'order_confirmation';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Order confirmation has been {$eventName}";
    }

    public function Order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function OrderConfirmationLines()
    {
        return $this->hasMany(OrderConfirmationLines::class, 'order_confirmation_id')->orderBy('ordre');
    }

    // ARC d'indice précédent que celui-ci remplace.
    public function supersedes()
    {
        return $this->belongsTo(OrderConfirmations::class, 'supersedes_id');
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

    // GED — le PDF émis est archivé ici avec le role 'arc'.
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    public function emailLogs()
    {
        return $this->morphMany(EmailLog::class, 'emailable');
    }

    /**
     * Type de document, attendu par les vues PDF partagées (pdf-sales) qui
     * masquent les conditions de règlement sur les documents internes.
     *
     * @return int
     */
    public function getTypeAttribute(): int
    {
        return 1;
    }

    /**
     * Un ARC n'est modifiable que tant qu'il n'a pas été envoyé.
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return (int) $this->statu === self::STATUS_IN_PROGRESS;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return in_array((int) $this->statu, [self::STATUS_SENT, self::STATUS_ACCEPTED, self::STATUS_SUPERSEDED], true);
    }

    /**
     * Get the total price attribute.
     *
     * Calculé sur les lignes figées de l'ARC, jamais sur celles de la commande.
     *
     * @return float
     */
    public function getTotalPriceAttribute()
    {
        $calculatorService = new OrderConfirmationCalculatorService($this);
        return $calculatorService->getTotalPrice();
    }

    /**
     * @return string
     */
    public function getFormattedTotalPriceAttribute()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->getTotalPriceAttribute(), $currency, config('app.locale'));
    }

    /**
     * Get the formatted creation date of the document.
     *
     * @return string
     */
    public function GetPrettyCreatedAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function GetshortCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([
                                                'code',
                                                'label',
                                                'customer_reference',
                                                'order_id',
                                                'revision',
                                                'statu',
                                                'is_current',
                                                'companies_id',
                                                'companies_contacts_id',
                                                'companies_addresses_id',
                                                'validity_date',
                                                'user_id',
                                                'comment',
                                                'issued_at',
                                                'sent_at',
                                                'customer_accepted_at']);
        // Chain fluent methods for configuration options
    }
}
