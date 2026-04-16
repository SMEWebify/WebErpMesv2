<?php

namespace App\Jobs;

use App\Models\Workflow\Deliverys;
use App\Services\SerialNumberService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateDeliverySerialNumbersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(private readonly int $deliveryId) {}

    public function handle(SerialNumberService $serialNumberService): void
    {
        $delivery = Deliverys::with(['DeliveryLines.OrderLine'])->find($this->deliveryId);

        if (!$delivery) {
            Log::warning("CreateDeliverySerialNumbersJob: delivery {$this->deliveryId} not found");
            return;
        }

        Log::debug("CreateDeliverySerialNumbersJob: delivery {$this->deliveryId} — {$delivery->DeliveryLines->count()} line(s)");

        foreach ($delivery->DeliveryLines as $deliveryLine) {
            $orderLine = $deliveryLine->OrderLine;

            Log::debug("CreateDeliverySerialNumbersJob: deliveryLine {$deliveryLine->id} — orderLine=" . ($orderLine?->id ?? 'null') . " product_id=" . ($orderLine?->product_id ?? 'null') . " qty={$deliveryLine->qty}");

            if (!$orderLine || !$orderLine->product_id) {
                continue;
            }

            $count = (int) round($deliveryLine->qty);

            Log::debug("CreateDeliverySerialNumbersJob: creating {$count} serial(s) for product {$orderLine->product_id}");

            for ($i = 0; $i < $count; $i++) {
                $serialNumberService->createSerialNumber(
                    $orderLine->product_id,
                    $orderLine->id,
                    status: 3, // expédié
                );
            }
        }
    }
}
