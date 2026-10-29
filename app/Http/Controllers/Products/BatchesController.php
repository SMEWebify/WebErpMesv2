<?php

namespace App\Http\Controllers\Products;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Products\Batch;
use App\Services\Files\FileRole;

class BatchesController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $batches = Batch::with(['product', 'supplier', 'purchaseReceiptLine.purchaseReceipt'])
            ->withCount('certificates')
            ->search($search)
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('products.batches-index', [
            'batcheslist' => $batches,
            'search'      => $search,
        ]);
    }

    /**
     * Fiche lot : coulée, certificat (GED du lot) et mouvements de stock, dont
     * les consommations sur tâche (lien lot → fabrication).
     */
    public function show(Batch $batch)
    {
        $batch->load([
            'product',
            'supplier',
            'purchaseReceiptLine.purchaseReceipt',
            'purchaseReceiptLine.purchaseLines.purchase',
            'stockMoves' => fn ($q) => $q->with(['StockLocationProducts', 'Task', 'UserManagement'])->orderByDesc('id'),
        ]);

        return view('products.batches-show', [
            'Batch'     => $batch,
            'fileRoles' => FileRole::forBatches(),
        ]);
    }
}
