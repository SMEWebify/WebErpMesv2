<?php

namespace App\Console\Commands;

use App\Models\Products\Products;
use App\Services\StockReservationService;
use Illuminate\Console\Command;

class RebuildStockReservations extends Command
{
    protected $signature   = 'stock:rebuild-reservations {--product= : Ne recalcule que ce products_id}';
    protected $description = 'Reconstruit les réservations de stock pour tous les composants achetés (products.purchased = 1)';

    public function handle(StockReservationService $service): int
    {
        $query = Products::where('purchased', 1);

        if ($productId = $this->option('product')) {
            $query->whereKey((int) $productId);
        }

        $products = $query->get(['id']);

        if ($products->isEmpty()) {
            $this->warn('Aucun composant acheté à recalculer.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $service->recomputeForProduct((int) $product->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("{$products->count()} composant(s) recalculé(s).");

        return self::SUCCESS;
    }
}
