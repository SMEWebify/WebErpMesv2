<?php

namespace App\Models\Workflow;

use Illuminate\Support\Number;
use Spatie\Activitylog\LogOptions;
use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ligne d'ARC — valeurs figées à l'émission.
 *
 * label, qty, selling_price, discount, vat_rate et delivery_date sont des copies,
 * pas des relations : c'est ce qui rend le document opposable. order_line_id ne
 * sert qu'à la traçabilité et à la comparaison avec la commande courante.
 */
class OrderConfirmationLines extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable = ['order_confirmation_id',
                            'order_line_id',
                            'ordre',
                            'code',
                            'label',
                            'qty',
                            'methods_units_id',
                            'unit_label',
                            'selling_price',
                            'discount',
                            'accounting_vats_id',
                            'vat_rate',
                            'delivery_date',
                            'comment',
                        ];

    // delivery_date reste une chaîne brute comme sur OrderLines : les vues PDF
    // partagées l'affichent telle quelle.

    public function OrderConfirmation()
    {
        return $this->belongsTo(OrderConfirmations::class, 'order_confirmation_id');
    }

    public function OrderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    // Conservées pour l'affichage du libellé courant, jamais pour les montants.
    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }

    /**
     * Total HT de la ligne, remise déduite.
     *
     * @return float
     */
    public function getTotalAttribute()
    {
        $total = $this->selling_price * $this->qty;
        $discountedTotal = $total - ($total * (($this->discount ?? 0) / 100));

        return round($discountedTotal, 2);
    }

    /**
     * @return string
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
     * @return string
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['order_confirmation_id', 'label', 'qty', 'selling_price', 'discount', 'delivery_date']);
        // Chain fluent methods for configuration options
    }
}
