<?php

namespace App\Services\Receivables;

use App\Enums\CreditBlockLevel;

/**
 * Encours d'une société à un instant donné, avec son plafond.
 */
final class CreditExposure
{
    public function __construct(
        public readonly int $companyId,
        public readonly float $invoicesDue,
        public readonly float $openOrders,
        public readonly ?float $limit,
        public readonly CreditBlockLevel $level,
    ) {}

    public function total(): float
    {
        return round($this->invoicesDue + $this->openOrders, 2);
    }

    public function hasLimit(): bool
    {
        return $this->limit !== null;
    }

    /** Part du plafond consommée (1 = plafond atteint), null sans plafond. */
    public function ratio(): ?float
    {
        if ($this->limit === null) {
            return null;
        }

        return $this->limit > 0 ? $this->total() / $this->limit : ($this->total() > 0 ? INF : 0.0);
    }

    public function toArray(): array
    {
        $ratio = $this->ratio();
        if ($ratio !== null) {
            // Plafond à zéro avec un encours : JSON ne connaît pas l'infini.
            $ratio = is_infinite($ratio) ? 999.0 : round($ratio, 4);
        }

        return [
            'invoices_due' => $this->invoicesDue,
            'open_orders'  => $this->openOrders,
            'total'        => $this->total(),
            'limit'        => $this->limit,
            'level'        => $this->level->value,
            'ratio'        => $ratio,
        ];
    }
}
