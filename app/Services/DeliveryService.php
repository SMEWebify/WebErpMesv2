<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Workflow\Deliverys;

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
    public function createDelivery($code, $label, $companyId, $companyAddressId, $companyContactId, $userId)
    {
        return Deliverys::create([
            'uuid' => Str::uuid(),
            'code' => $code,
            'label' => $label,
            'companies_id' => $companyId,
            'companies_addresses_id' => $companyAddressId,
            'companies_contacts_id' => $companyContactId,
            'user_id' => $userId,
        ]);
    }
}
