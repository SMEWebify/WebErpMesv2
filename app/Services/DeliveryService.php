<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;

class DeliveryService
{
    /**
     * New Delivery
     *
     * @param string $code
     * @param string $label
     * @param object $companyId
     * @param object $companyAddressId
     * @param object $compacompanyContactIdnyId
     * @param object $userId
     * @return Deliverys
     */
    public function createDelivery($code, $label, $companyId, $companyAddressId, $companyContactId, $userId, $customerReference = null)
    {
        return Deliverys::create([
            'uuid'                   => Str::uuid(),
            'code'                   => $code,
            'label'                  => $label,
            'companies_id'           => $companyId,
            'companies_addresses_id' => $companyAddressId,
            'companies_contacts_id'  => $companyContactId,
            'user_id'                => $userId,
            'customer_reference'     => $customerReference,
        ]);
    }

    /**
     * Recompute and persist a delivery header invoice_status from its lines.
     *
     * Line invoice_status values:
     *   1 = Facturable, 2 = Non facturable, 3 = Partiellement facturé, 4 = Facturé
     *
     * Header is derived so a fully settled delivery (everything invoiced and/or
     * marked non-chargeable) leaves the "à facturer" lists, which is exactly the
     * case of an unbilled BL or a BL tied to a cancelled order.
     *
     * @param int $deliveryId
     * @return void
     */
    public function recomputeInvoiceStatus(int $deliveryId): void
    {
        $statuses = DeliveryLines::where('deliverys_id', $deliveryId)
            ->pluck('invoice_status')
            ->map(fn ($s) => (int) $s);

        if ($statuses->isEmpty()) {
            return;
        }

        $allInvoiced  = $statuses->every(fn ($s) => $s === 4);
        $allSettled   = $statuses->every(fn ($s) => in_array($s, [2, 4], true));
        $anyInvoiced  = $statuses->contains(4);
        $allToInvoice = $statuses->every(fn ($s) => $s === 1);

        if ($allInvoiced) {
            $status = 4;                       // Facturé
        } elseif ($allSettled) {
            $status = $anyInvoiced ? 4 : 2;    // tout réglé : facturé, sinon non facturable
        } elseif ($allToInvoice) {
            $status = 1;                       // Facturable
        } else {
            $status = 3;                       // Partiellement
        }

        Deliverys::where('id', $deliveryId)->update(['invoice_status' => $status]);
    }
}
