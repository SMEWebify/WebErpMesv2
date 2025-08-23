<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Products\Batch;

class BatchService
{
    public function createBatch($productId, $productionDate = null, $expirationDate = null)
    {
        return Batch::create([
            'code' => Str::uuid(),
            'product_id' => $productId,
            'production_date' => $productionDate,
            'expiration_date' => $expirationDate,
        ]);
    }

    public function closeBatch(Batch $batch)
    {
        $batch->update(['closed_at' => now()]);
        return $batch;
    }
}
