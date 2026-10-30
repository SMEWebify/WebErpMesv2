<?php

namespace App\Models\Purchases;

use App\Models\Planning\Task;
use Illuminate\Support\Number;
use App\Models\Products\Products;
use App\Services\Purchases\PurchaseCriticality;
use Spatie\Activitylog\LogOptions;
use App\Models\Purchases\Purchases;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\StockLocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseLines extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['ind', 'purchases_id', 
                            'tasks_id', 
                            'ordre',
                            'code',
                            'product_id',
                            'label',
                            'supplier_ref',
                            'qty',
                            'selling_price',
                            'discount',
                            'unit_price_after_discount',
                            'total_selling_price',
                            'receipt_qty',
                            'invoiced_qty',
                            'delivery_date',
                            'confirmed_delivery_date',
                            'methods_units_id',
                            'accounting_vats_id',
                            'stock_locations_id',
                            'certificate_type',
                            'construction_code',
                            'nuclear_level',
                        ];

    /**
     * Criticité (certificat, code de construction, niveau) reprise de l'article à
     * la création, pour chaque champ que l'appelant n'a pas fourni : tous les
     * chemins de création (écran, demande d'achat, réappro, réception) en héritent.
     * Une valeur fournie, même vide, est gardée : l'écran peut retirer une exigence.
     */
    protected static function booted(): void
    {
        static::creating(function (PurchaseLines $line) {
            $missing = array_values(array_filter(
                PurchaseCriticality::FIELDS,
                fn ($field) => ! array_key_exists($field, $line->getAttributes())
            ));

            if (! $line->product_id || $missing === []) {
                return;
            }

            $product = Products::query()->select(array_merge(['id'], $missing))->find($line->product_id);

            foreach ($missing as $field) {
                $line->setAttribute($field, $product?->{$field});
            }
        });
    }

    public function tasks()
    {
        return $this->belongsTo(Task::class, 'tasks_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchases::class, 'purchases_id');
    }

    public function purchaseReceiptLines()
    {
        return $this->hasMany(PurchaseReceiptLines::class, 'purchase_line_id');
    }

    public function unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class, 'stock_locations_id');
    }

    /**
     * Prix unitaire remisé et total de ligne, tels que les stockent
     * unit_price_after_discount / total_selling_price (decimal 10,3).
     *
     * Même formule que l'affichage (getTotalAttribute, PurchaseCalculatorService) :
     * ces colonnes ne sont qu'un cache, lu par l'écran « En attente de
     * facturation », le dossier d'affaire et les KPI achats.
     */
    public static function netPrices($sellingPrice, $qty, $discount): array
    {
        $unit = (float) $sellingPrice * (1 - ((float) ($discount ?? 0) / 100));

        return [
            'unit_price_after_discount' => round($unit, 3),
            'total_selling_price'       => round($unit * (float) $qty, 3),
        ];
    }

    public function getTotalAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        
        $price = $this->selling_price;
        $qty = $this->qty;
        $discount = $this->discount ?? 0;
        
        $total = $price * $qty;
        $discountedTotal = $total - ($total * ($discount / 100));

        return Number::currency($discountedTotal, $currency, config('app.locale'));
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
    public function getFormattedSellingPriceAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->selling_price, $currency, config('app.locale'));
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
        return \Illuminate\Support\Carbon::parse($this->created_at)->locale(app()->getLocale())->translatedFormat('d F Y');
    }

    public function getActivitylogOptions(): LogOptions
    {
        // AR fournisseur et date confirmée : qui a changé la date de référence des retards
        return LogOptions::defaults()->logOnly([ 'code', 'label', 'statu', 'confirmed_delivery_date', 'supplier_ack_at', 'supplier_ack_reference']);
        // Chain fluent methods for configuration options
    }
}
