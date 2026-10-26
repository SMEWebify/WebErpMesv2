<?php

namespace App\Models\Accounting;

use App\Traits\HasDefaultTrait;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AccountingPaymentConditions
 *
 * This model represents the payment conditions in the accounting system.
 * It uses the HasFactory and HasDefaultTrait traits.
 *
 */
class AccountingPaymentConditions extends Model
{
    use HasFactory; use HasDefaultTrait;

    // Fillable attributes for mass assignment
    protected $fillable= ['code',  'label',  'number_of_month',  'number_of_day',  'month_end',  'default'];

    /**
     * Échéance d'un document daté de $from : mois, puis jours, puis fin de mois.
     * « 30 jours fin de mois » au 10 janvier donne donc le 28 (ou 29) février.
     * month_end vaut 1 pour « oui » (2 pour « non » depuis l'écran comptable, 0 depuis
     * l'assistant d'installation).
     */
    public function dueDateFrom(CarbonInterface $from): Carbon
    {
        $date = Carbon::parse($from)->startOfDay()
            ->addMonthsNoOverflow((int) $this->number_of_month)
            ->addDays((int) $this->number_of_day);

        return (int) $this->month_end === 1 ? $date->endOfMonth()->startOfDay() : $date;
    }
}
