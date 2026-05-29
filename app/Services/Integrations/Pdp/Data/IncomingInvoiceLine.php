<?php

namespace App\Services\Integrations\Pdp\Data;

/**
 * Ligne d'une facture fournisseur entrante, extraite d'un document Factur-X.
 */
final class IncomingInvoiceLine
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $sellerAssignedId,
        public readonly float $quantity,
        public readonly ?string $unitCode,
        public readonly float $lineTotal,
    ) {}

    public function toArray(): array
    {
        return [
            'name'               => $this->name,
            'seller_assigned_id' => $this->sellerAssignedId,
            'quantity'           => $this->quantity,
            'unit_code'          => $this->unitCode,
            'line_total'         => $this->lineTotal,
        ];
    }
}
