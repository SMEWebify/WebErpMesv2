<?php

namespace App\Models\Workflow;

use Illuminate\Support\Number;
use App\Models\Workflow\Invoices;
use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\DeliveryLines;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingEntry;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ligne de facture.
 *
 * Deux natures de lignes cohabitent :
 *  - la ligne issue d'une commande (`order_line_id` renseigné), qui reste la
 *    très grande majorité des cas ;
 *  - la ligne libre (`order_line_id` null), saisie directement sur une facture
 *    en brouillon : frais de port, frais de dossier, prestation ponctuelle.
 *
 * Les accesseurs `display_*` / `resolved_*` donnent la valeur à utiliser sans
 * avoir à savoir de quelle nature est la ligne : ils lisent le champ porté par
 * la ligne de facture et retombent sur la ligne de commande pour les lignes
 * antérieures au snapshot.
 */
class InvoiceLines extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['invoices_id',
                            'order_line_id',
                            'delivery_line_id',
                            'label',
                            'code',
                            'product_id',
                            'methods_units_id',
                            'ordre',
                            'qty',
                            'unit_price',
                            'discount',
                            'vat_rate',
                            'accounting_vats_id',
                            'accounting_allocation_id',
                            'invoice_status',
                        ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoices_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    public function deliveryLine()
    {
        return $this->belongsTo(DeliveryLines::class, 'delivery_line_id');
    }

    public function Product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }

    // Relation avec AccountingEntry pour l'entrée comptable liée à cette ligne de facture
    public function accountingEntry()
    {
        return $this->hasOne(AccountingEntry::class, 'invoice_line_id');
    }

    /**
     * Une ligne libre n'a pas de ligne de commande d'origine.
     */
    public function getIsFreeLineAttribute(): bool
    {
        return $this->order_line_id === null;
    }

    /**
     * Désignation à afficher : celle portée par la ligne de facture, sinon
     * celle de la ligne de commande.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? $this->orderLine?->label ?? '';
    }

    public function getDisplayCodeAttribute(): string
    {
        return $this->code ?? $this->orderLine?->code ?? '';
    }

    /**
     * Unité résolue (objet MethodsUnits) ou null.
     */
    public function getDisplayUnitAttribute()
    {
        return $this->methods_units_id ? $this->Unit : $this->orderLine?->Unit;
    }

    public function getDisplayUnitLabelAttribute(): string
    {
        return $this->display_unit->label ?? '';
    }

    public function getDisplayUnitCodeAttribute(): ?string
    {
        return $this->display_unit->code ?? null;
    }

    public function getResolvedUnitPriceAttribute(): float
    {
        return (float) ($this->unit_price ?? $this->orderLine?->selling_price ?? 0);
    }

    public function getResolvedDiscountAttribute(): float
    {
        return (float) ($this->discount ?? $this->orderLine?->discount ?? 0);
    }

    public function getResolvedVatRateAttribute(): float
    {
        return (float) ($this->vat_rate ?? $this->orderLine?->VAT?->rate ?? 0);
    }

    /**
     * Identifiant de TVA servant de clé de regroupement dans la ventilation.
     */
    public function getResolvedVatIdAttribute(): ?int
    {
        return $this->accounting_vats_id ?? $this->orderLine?->accounting_vats_id;
    }

    /**
     * Montant HT de la ligne, remise déduite.
     */
    public function getLineTotalAttribute(): float
    {
        return (float) $this->qty * $this->resolved_unit_price * (1 - $this->resolved_discount / 100);
    }

    /**
     * Get the formatted selling price attribute.
     *
     * This method retrieves the selling price attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted selling price.
     */
    public function getFormattedSellingPriceAttribute()
    {
        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->resolved_unit_price, $currency, config('app.locale'));
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['invoices_id', 'invoice_status']);
        // Chain fluent methods for configuration options
    }
}
