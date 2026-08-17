<?php

namespace App\Models\Planning;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Number;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\ReturnLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\TaskActivities;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public const PRIORITY_HIGH = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_LOW = 3;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',
                            'label', 
                            'ordre',
                            'quote_lines_id',
                            'order_lines_id',
                            'products_id',
                            'sub_assembly_id',
                            'methods_services_id',  
                            'component_id',
                            'seting_time', 
                            'unit_time', 
                            'remaining_time', 
                            'status_id',
                            'user_id',
                            'priority',
                            'type',
                            'delay',
                            'due_date',
                            'qty',
                            'qty_init',
                            'qty_aviable',
                            'unit_cost',
                            'unit_price',
                            'methods_units_id',
                            'x_size', 
                            'y_size', 
                            'z_size', 
                            'x_oversize',
                            'y_oversize',
                            'z_oversize',
                            'diameter',
                            'diameter_oversize',
                            'to_schedule',
                            'start_date',
                            'end_date',
                            'not_recalculate',
                            'material', 
                            'thickness', 
                            'weight',
                            'methods_tools_id',
                            'secondary_user_id',
                            'origin'];

    protected $appends = ["open"];

    protected $casts = [
        'due_date' => 'date',
        'priority' => 'integer',
    ];

    /**
     * Memoization per-instance (request-scoped) pour éviter les rappels DB
     * répétés dans les boucles de calcul (BusinessBalance, calculators, Gantt).
     */
    private ?float $cachedOrderQtyLine = null;
    private ?int $cachedLogStartTime = null;
    private ?int $cachedLogEndTime = null;

    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_HIGH => __('High'),
            self::PRIORITY_MEDIUM => __('Medium'),
            self::PRIORITY_LOW => __('Low'),
        ];
    }

    public function getPriorityLabelAttribute(): string
    {
        $labels = self::priorityLabels();

        return $labels[$this->priority] ?? $labels[self::PRIORITY_MEDIUM];
    }

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    /**
     * Define a many-to-many relationship with the MethodsRessources model.
     * This relationship uses the 'task_resources' pivot table and includes
     * the pivot attributes 'role' (machine / labor) and 'source' (auto,
     * manual, forced), which replace the former autoselected_ressource /
     * userforced_ressource integer flags.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function resources() {
        return $this->belongsToMany(MethodsRessources::class, 'task_resources')
                    ->withPivot(['role', 'source', 'load_factor'])
                    ->withTimestamps();
    }

    /**
     * Ressource machine affectée à la tâche — au plus une seule,
     * garantie par l'index unique (task_id, methods_ressources_id, role).
     */
    public function machineResource()
    {
        return $this->resources()->withPivotValue('role', TaskResources::ROLE_MACHINE);
    }

    /**
     * Ressource main-d'œuvre affectée à la tâche. Distincte de user_id /
     * secondary_user_id, qui désignent le responsable et non la capacité
     * consommée : une opération manuelle se planifie sur cette ressource.
     */
    public function laborResource()
    {
        return $this->resources()->withPivotValue('role', TaskResources::ROLE_LABOR);
    }

    /**
     * Limite aux tâches productives : les lignes matière, fournitures et
     * sous-traitance ne consomment aucune capacité interne, elles n'ont donc
     * ni ressource à affecter ni à être comptées comme « sans ressource ».
     */
    public function scopeProductive(Builder $query): Builder
    {
        return $query->whereHas('service', function (Builder $serviceQuery) {
            $serviceQuery->where('type', MethodsServices::TYPE_PRODUCTIVE);
        });
    }

    
    /**
     * Define a belongs-to relationship with the QuoteLines model.
     * This indicates that each task belongs to a single quote line.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function QuoteLines()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }

        /**
     * Define a belongs-to relationship with the OrderLines model.
     * This indicates that each task belongs to a single order line.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function OrderLines()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }

    /**
     * Define a one-to-many relationship with the PurchaseLines model.
     * This indicates that a task can have multiple purchase lines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseLines()
    {
        return $this->hasMany(PurchaseLines::class, 'tasks_id');
    }

    /**
     * Define a one-to-many relationship with the StockMove model.
     * This indicates that a task can have multiple stock moves.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function StockMove()
    {
        return $this->hasMany(StockMove::class);
    }

    public function returnLines()
    {
        return $this->hasMany(ReturnLines::class, 'original_task_id');
    }

    public function reworkReturnLines()
    {
        return $this->hasMany(ReturnLines::class, 'rework_task_id');
    }

    /**
     * Define a belongs-to relationship with the Products model.
     * This indicates that each task belongs to a single product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Products()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    /**
     * Define a belongs-to relationship with the SubAssembly model.
     * This indicates that each task belongs to a single sub-assembly.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function SubAssembly() //https://github.com/SMEWebify/WebErpMesv2/issues/334
    {
        return $this->belongsTo(SubAssembly::class, 'sub_assembly_id');
    }

    /**
     * Define a belongs-to relationship with the Products model.
     * This indicates that each task belongs to a single component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Component()
    {
        return $this->belongsTo(Products::class, 'component_id');
    }
    
    /**
     * Define a belongs-to relationship with the MethodsUnits model.
     * This indicates that each task belongs to a single unit.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    /**
     * Define a one-to-many relationship with the QualityNonConformity model.
     * This indicates that a task can have multiple quality non-conformities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function QualityNonConformity()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    /**
     * Define a belongs-to relationship with the MethodsTools model.
     * This indicates that each task belongs to a single method tool.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function MethodsTools()
    {
        return $this->belongsTo(MethodsTools::class, 'methods_tools_id');
    }

    public function overlapsWithExistingToolBooking(): bool
    {
        if (!$this->methods_tools_id || !$this->start_date || !$this->end_date) {
            return false;
        }

        return self::where('methods_tools_id', $this->methods_tools_id)
                    ->where('id', '!=', $this->id ?? 0)
                    ->where(function (Builder $query) {
                        $query->where(function (Builder $query) {
                                    $query->where('start_date', '<=', $this->start_date)
                                            ->where('end_date', '>=', $this->start_date);
                                })
                                ->orWhere(function (Builder $query) {
                                    $query->where('start_date', '<=', $this->end_date)
                                            ->where('end_date', '>=', $this->end_date);
                                })
                                ->orWhere(function (Builder $query) {
                                    $query->where('start_date', '>=', $this->start_date)
                                            ->where('end_date', '<=', $this->end_date);
                                });
                    })
                    ->exists();
    }

    /**
     * Define a belongs-to relationship with the User model.
     * This indicates that each task belongs to a single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function secondaryAssignee()
    {
        return $this->belongsTo(User::class, 'secondary_user_id');
    }

    /**
     * Define a belongs-to relationship with the Status model.
     * This indicates that each task belongs to a single status.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Get the quantity used for time and cost calculations.
     *
     * This method retrieves the order line quantity when available. If the task
     * is tied to a quote line (before an order exists), it falls back to the
     * quote line quantity or the task's own quantity.
     *
     * @return int The quantity of the related line or task.
     */
    public function GetOrderQtyLine()
    {
        if ($this->cachedOrderQtyLine !== null) {
            return $this->cachedOrderQtyLine;
        }

        if (!empty($this->order_lines_id)) {
            // Privilégie la relation déjà chargée (eager-load) — évite un SELECT par tâche
            // dans les boucles d'agrégation (load-planning, business balance, gantt).
            if ($this->relationLoaded('OrderLines') && $this->OrderLines) {
                return $this->cachedOrderQtyLine = (float) ($this->OrderLines->qty ?? 0);
            }
            $OrderLine = OrderLines::find($this->order_lines_id);
            return $this->cachedOrderQtyLine = (float) ($OrderLine->qty ?? 0);
        }

        if (!empty($this->quote_lines_id)) {
            if ($this->relationLoaded('QuoteLines') && $this->QuoteLines) {
                if (!empty($this->QuoteLines->qty)) {
                    return $this->cachedOrderQtyLine = (float) $this->QuoteLines->qty;
                }
            } else {
                $QuoteLine = QuoteLines::find($this->quote_lines_id);
                if (!empty($QuoteLine->qty)) {
                    return $this->cachedOrderQtyLine = (float) $QuoteLine->qty;
                }
            }
        }

        return $this->cachedOrderQtyLine = (float) ($this->qty ?? 0);
    }

    /**
     * Calculate the product time.
     *
     * This method calculates the product time by multiplying the order quantity by the unit time.
     *
     * @return float The product time.
     */
    public function ProductTime()
    {
        return $this->GetOrderQtyLine()*$this->unit_time;
    }

    /**
     * Get the formatted unit cost attribute.
     *
     * This method retrieves the unit cost  attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted unit cost .
     */
    public function getFormattedUnitCostAttribute()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->unit_cost, $currency, config('app.locale'));

    }

    /**
     * Get the formatted unit price attribute.
     *
     * This method retrieves the unit price  attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted unit price .
     */
    public function getFormattedUnitPriceAttribute()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->unit_price, $currency, config('app.locale'));
    }

    /**
     * Calculate the total cost.
     *
     * This method calculates the total cost by multiplying the order quantity by the unit cost.
     * The result is rounded to 2 decimal places.
     *
     * @return float The total cost.
     */
    public function TotalCost()
    {
        return round($this->GetOrderQtyLine()*$this->unit_cost,2);
    }

    /**
     * Get the formatted total cost attribute.
     *
     * This method retrieves the total cost  attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted total cost .
     */
    public function getFormattedTotalCostAttribute()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->TotalCost(), $currency, config('app.locale'));
    }

    /**
     * Calculate the total price.
     *
     * This method calculates the total price by multiplying the order quantity by the unit price.
     * The result is rounded to 2 decimal places.
     *
     * @return float The total price.
     */
    public function TotalPrice()
    {
        return round($this->GetOrderQtyLine()*$this->unit_price,2);
    }

    /**
     * Get the formatted total price attribute.
     *
     * This method retrieves the total price  attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted total price .
     */
    public function getFormattedTotalPriceAttribute()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->TotalPrice(), $currency, config('app.locale'));
    }

    /**
     * Calculate the margin.
     *
     * This method calculates the margin as a percentage by dividing the unit price by the unit cost,
     * subtracting 1, and then multiplying by 100. The result is rounded to 2 decimal places.
     *
     * @return float The margin percentage.
     */
    public function Margin()
    {
        return round((($this->unit_price/$this->unit_cost)-1)*100,2);
    }
    
    /**
     * Calculate the total time.
     *
     * This method calculates the total time by adding the product time to the setting time.
     *
     * @return float The total time.
     */
    public function TotalTime()
    {
        return round($this->ProductTime() + $this->seting_time, 2);
    }

    /**
     * Define a one-to-many relationship with the TaskActivities model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskActivities()
    {
        return $this->hasMany(TaskActivities::class);
    }

    /**
     * Get the total log start time.
     *
     * This method calculates the total log start time in seconds by summing the difference
     * between the current date-time and the timestamp of all task activities of type 1.
     *
     * @return int The total log start time in seconds.
     */
    public function getTotalLogStartTime()
    {
        if ($this->cachedLogStartTime !== null) {
            return $this->cachedLogStartTime;
        }
        $current_date_time = Carbon::now()->toDateTimeString();
        return $this->cachedLogStartTime = (int) TaskActivities::where('task_id', $this->id)
                                ->where('type', 1)
                                ->sum(DB::raw("TIMESTAMPDIFF(SECOND, timestamp, '". $current_date_time ."')"));
    }

    /**
     * Get the total log end time.
     *
     * This method calculates the total log end time in seconds by summing the difference
     * between the current date-time and the timestamp of all task activities of type 2 or 3.
     *
     * @return int The total log end time in seconds.
     */
    public function getTotalLogEndTime()
    {
        if ($this->cachedLogEndTime !== null) {
            return $this->cachedLogEndTime;
        }
        $current_date_time = Carbon::now()->toDateTimeString();
        return $this->cachedLogEndTime = (int) TaskActivities::where('task_id', $this->id)
                                ->where(function (Builder $query) {
                                    return $query->where('type', 2)
                                                ->orWhere('type', 3);
                                })
                                ->sum(DB::raw("TIMESTAMPDIFF(SECOND, timestamp, '". $current_date_time ."')"));
    }

    /**
     * Get the total log time.
     *
     * This method calculates the total log time in hours by subtracting the total log end time
     * from the total log start time and dividing by 3600. The result is rounded to 2 decimal places.
     *
     * @return float The total log time in hours.
     */
    public function getTotalLogTime()
    {
        return   round(($this->getTotalLogStartTime()-$this->getTotalLogEndTime())/3600,2);
    }

    /**
     * Get the total realized cost.
     *
     * This method calculates the total realized cost by multiplying the total log time
     * by the hourly rate of the service. The result is rounded to 2 decimal places.
     *
     * @return float The total realized cost.
     */
    public function getTotalRealizedCost()
    {
        return   round($this->getTotalLogTime()*$this->service->hourly_rate,2);
    }

    /**
     * Calculate the progress.
     *
     * This method calculates the progress as a percentage by dividing the total log time
     * by the total time and multiplying by 100. The result is rounded to 2 decimal places.
     * If the total time is less than or equal to 0, it returns 0.
     *
     * @return float The progress percentage.
     */
    public function progress()
    {
        if($this->TotalTime() <= 0){
            return 0;
        }
        return   round($this->getTotalLogTime()/$this->TotalTime()*100,2);
    }

    /**
     * Get the total log good quantity.
     *
     * This method calculates the total log good quantity by summing the good quantity
     * of all task activities of type 4.
     *
     * @return int The total log good quantity.
     */
    public function getTotalLogGoodQt()
    {
        return   TaskActivities::where('task_id', $this->id)
                                ->where('type', 4)
                                ->sum('good_qt');
    }

    /**
     * Get the total log bad quantity.
     *
     * This method calculates the total log bad quantity by summing the bad quantity
     * of all task activities of type 5.
     *
     * @return int The total log bad quantity.
     */
    public function getTotalLogBadQt()
    {
        return   TaskActivities::where('task_id', $this->id)
                                ->where('type', 5)
                                ->sum('bad_qt');
    }

    /**
     * Get the total net good quantity.
     *
     * This method calculates the total net good quantity by subtracting the total log bad quantity
     * from the total log good quantity.
     *
     * @return int The total net good quantity.
     */
    public function getTotalNetGoodQt()
    {
        return  $this->getTotalLogGoodQt()-$this->getTotalLogBadQt();
    }

    /**
     * Get the formatted end date.
     *
     * This accessor method returns the end date formatted as 'Y-m-d'.
     * If the end date is null, it returns "NULL".
     *
     * @return string The formatted end date or "NULL".
     */
    public function getFormattedEndDateAttribute()
    {
        if(!is_null($this->end_date)){
            return date('Y-m-d', strtotime($this->end_date));
        }
        return "NULL";
    }

    // Calculation of availability time
    public function getAvailabilityAttribute()
    {
        $plannedTime = $this->TotalTime();
        if ($plannedTime <= 0) {
            return 0;
        }
        $operationalTime = $this->getTotalLogTime();
        return $operationalTime / $plannedTime;
    }

    // Performance calculation
    public function getPerformanceAttribute()
    {
        $producedQty = $this->getTotalLogGoodQt();
        $theoreticalQty = $this->GetOrderQtyLine(); 
        if ($theoreticalQty <= 0) {
            return 0;
        }
        return $producedQty / $theoreticalQty;
    }

    // Quality calculation
    public function getQualityAttribute()
    {
        $goodQty = $this->getTotalLogGoodQt();
        $totalQty = $goodQty + $this->getTotalLogBadQt();
        if ($totalQty <= 0) {
            return 0;
        }
        return $goodQty / $totalQty;
    }

    // Quality calculation
    public function getQualityRequiredAttribute()
    {
        $qualityRequired = $this->qty * $this->GetOrderQtyLine();
        if ($qualityRequired <= 0) {
            return $this->GetOrderQtyLine();
        }
        return $qualityRequired;
    }

    // calculation TRS/OEE
    public function getTRSAttribute()
    {
        $availability = $this->availability;
        $performance = $this->performance; 
        $quality = $this->quality;
        $trs = $availability * $performance * $quality * 100; 

        return number_format($trs, 2);
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
        return date('d F Y', strtotime($this->created_at));
    }

    public function getOpenAttribute(){
        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'quote_lines_id', 'order_lines_id', 'products_id']);
        // Chain fluent methods for configuration options
    }

    /**
     * Reverse-lookup pour les events entrants d'intégration (Nest2Prod, etc.).
     *
     * Clé métier stable (voir ligne directrice) :
     *  - $ofCode       : "OF" + orderLine.id (format produit par N2PPayloadBuilder)
     *  - $lineRef      : orderLine.id (string), redondant avec ofCode mais transporté
     *  - $operationCode: task.code si défini, sinon "op-<task.id>" (voir
     *                    N2PPayloadBuilder::OPERATION_CODE_FALLBACK_PREFIX)
     */
    public static function resolveByExternalRef(string $ofCode, string $lineRef, string $operationCode): ?self
    {
        $orderLineId = null;
        if (str_starts_with($ofCode, 'OF')) {
            $orderLineId = (int) substr($ofCode, 2);
        } elseif (ctype_digit($lineRef)) {
            $orderLineId = (int) $lineRef;
        }

        if ($orderLineId === null || $orderLineId <= 0 || $operationCode === '') {
            return null;
        }

        $query = static::query()->where('order_lines_id', $orderLineId);

        $fallbackPrefix = \App\Services\N2P\N2PPayloadBuilder::OPERATION_CODE_FALLBACK_PREFIX;
        if (str_starts_with($operationCode, $fallbackPrefix)) {
            $taskId = (int) substr($operationCode, strlen($fallbackPrefix));
            return $query->whereKey($taskId)->first();
        }

        // tasks.code n'est pas unique par order_line — deux ops "PLIAGE" sur
        // la même ligne existent. On trie par ordre puis id pour cibler la
        // 1re occurrence (celle qui doit démarrer/finir en premier) et on
        // logue une ambiguïté détectée pour audit.
        $matches = $query->where('code', $operationCode)->orderBy('ordre')->orderBy('id')->get();
        if ($matches->count() > 1) {
            \Illuminate\Support\Facades\Log::channel('n2p')->warning('Ambiguous task code on order line', [
                'order_line_id' => $orderLineId,
                'operation_code' => $operationCode,
                'candidate_ids' => $matches->pluck('id')->all(),
                'chosen_id' => $matches->first()->id,
            ]);
        }

        return $matches->first();
    }
}
