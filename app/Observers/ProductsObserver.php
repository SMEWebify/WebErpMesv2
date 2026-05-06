<?php

namespace App\Observers;

use App\Models\Products\Products;
use App\Models\Products\ProductPriceHistory;
use Illuminate\Support\Facades\Auth;

class ProductsObserver
{
    public function created(Products $product): void
    {
        $userId = Auth::id();
        $today  = now()->toDateString();

        if (!is_null($product->purchased_price)) {
            ProductPriceHistory::create([
                'products_id' => $product->id,
                'type'        => 'purchase',
                'price'       => $product->purchased_price,
                'started_at'  => $today,
                'ended_at'    => null,
                'user_id'     => $userId,
            ]);
        }

        if (!is_null($product->selling_price)) {
            ProductPriceHistory::create([
                'products_id' => $product->id,
                'type'        => 'sale',
                'price'       => $product->selling_price,
                'started_at'  => $today,
                'ended_at'    => null,
                'user_id'     => $userId,
            ]);
        }
    }

    public function updated(Products $product): void
    {
        if (!$product->isDirty(['purchased_price', 'selling_price'])) {
            return;
        }

        $userId = Auth::id();
        $today  = now()->toDateString();

        if ($product->isDirty('purchased_price')) {
            ProductPriceHistory::where('products_id', $product->id)
                ->where('type', 'purchase')
                ->whereNull('ended_at')
                ->update(['ended_at' => $today]);

            if (!is_null($product->purchased_price)) {
                ProductPriceHistory::create([
                    'products_id' => $product->id,
                    'type'        => 'purchase',
                    'price'       => $product->purchased_price,
                    'started_at'  => $today,
                    'ended_at'    => null,
                    'user_id'     => $userId,
                ]);
            }
        }

        if ($product->isDirty('selling_price')) {
            ProductPriceHistory::where('products_id', $product->id)
                ->where('type', 'sale')
                ->whereNull('ended_at')
                ->update(['ended_at' => $today]);

            if (!is_null($product->selling_price)) {
                ProductPriceHistory::create([
                    'products_id' => $product->id,
                    'type'        => 'sale',
                    'price'       => $product->selling_price,
                    'started_at'  => $today,
                    'ended_at'    => null,
                    'user_id'     => $userId,
                ]);
            }
        }
    }
}
