<?php

namespace App\Services;

use App\Models\Products\SerialNumbers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class SerialNumberService
{
    public function createSerialNumber($productId, $OrderLineID, $status = 1)
    {
        $serial = SerialNumbers::create([
            'products_id' => $productId,
            'order_line_id' => $OrderLineID,
            'serial_number' => Str::uuid(),
            'status' => $status,
        ]);

        activity()
            ->performedOn($serial)
            ->causedBy(Auth::user())
            ->withProperties(['status' => $status])
            ->log('serial number created');

        return $serial;
    }

    public function updateStatus(SerialNumbers $serialNumber, int $status): SerialNumbers
    {
        $serialNumber->update(['status' => $status]);

        activity()
            ->performedOn($serialNumber)
            ->causedBy(Auth::user())
            ->withProperties(['status' => $status])
            ->log('serial number status updated');

        return $serialNumber;
    }
}
