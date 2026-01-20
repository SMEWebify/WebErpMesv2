<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\GuestVisits;
use Illuminate\Support\Number;
use App\Models\Workflow\Quotes;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\OrderRating;
use App\Models\Workflow\OrderSite;
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

    // Fillable attributes for mass assignment
    protected $fillable= ['id',
                            'uuid',
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
                            'csv_file_name',
                            'reviewed_by',
                            'reviewed_at',
                            'review_decision',
                            'change_requested_by',
                            'change_reason',
                            'change_approved_at',
                            'n2p_last_push_at',
                            'n2p_last_push_status',
                            'n2p_last_push_error',
                        ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'change_approved_at' => 'datetime',
        'n2p_last_push_at' => 'datetime',
    ];

    public function setOriginal($key, $value = null): self
    {
        if (is_array($key)) {
            $this->original = $key;
            return $this;
        }

        $this->original[$key] = $value;

        return $this;
    }

    // Only log changes
    protected static $logOnlyDirty = true;

    // Add a contextual log
    protected static $logName = 'order';

    // Do not store empty values
    protected static $submitEmptyLogs = false;

    // Customize the log description
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Order has been {$eventName}";
    }

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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function changeRequester()
    {
        return $this->belongsTo(User::class, 'change_requested_by');
    }

    public function OrderSite()
    {
        return $this->hasOne(OrderSite::class, 'order_id', 'id');
    }
    
    public function getPurchaseLinesCountAttribute()
    {
        return $this->OrderLines->sum(function ($orderLine) {
            return $orderLine->Task->sum(function ($task) {
                return $task->purchaseLines->count();
            });
        });
    }

    // Relationship with the files associated with the order
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

    /**
     * Get the delay information attribute.
     *
     * This method calculates the delay information based on the validity date of the order.
     * It returns a string indicating whether the order is late, due today, or due in a certain number of days.
     *
     * @return string The delay information.
     */
    public function getDelayInfoAttribute()
    {
        $validityDate = Carbon::parse($this->validity_date);
        $today = Carbon::today();
    
        if ($validityDate->isPast()) {
            return __('general_content.days_late_trans_key', ['days' => ceil($today->diffInDays(now()))]);
        } elseif ($validityDate->isToday()) {
            return __('general_content.to_deliver_today_trans_key');
        } else {
            return __('general_content.delivery_in_days_trans_key', ['days' => ceil($today->diffInDays($validityDate))]);
        }
    }

    /**
     * Get the total price attribute.
     *
     * This method calculates the total price of the order using the
     * OrderCalculatorService and returns the calculated value.
     *
     * @return float The total price of the order.
     */
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

        return round($SumPercent/$TotalCountLines,2);
    }

    public function getTotalWeightAttribute()
    {
        return $this->OrderLines->sum(function ($orderLine) {
            $weight = optional($orderLine->OrderLineDetails)->weight ?? 0;

            return (float) $weight * (float) $orderLine->qty;
        });
    }

    // Relationship with the Rating associated with the Purchases
    public function Rating()
    {
        return $this->hasMany(OrderRating::class);
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
        return LogOptions::defaults()->logOnly([
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
                                                'csv_file_name',
                                                'reviewed_by',
                                                'reviewed_at',
                                                'review_decision',
                                                'change_requested_by',
                                                'change_reason',
                                                'change_approved_at']);
        // Chain fluent methods for configuration options
    }
}
