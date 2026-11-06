<?php

namespace App\Http\Controllers\Products;

use App\Models\Planning\Task;
use App\Services\BatchService;
use App\Services\StockService;
use App\Models\Products\Batch;
use Illuminate\Support\Facades\DB;
use App\Models\Products\Stocks;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Models\Workflow\OrderLines;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use Illuminate\Support\Facades\Log;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Services\StockCalculationService;
use App\Services\Stock\SheetRemnants;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\StoreStockMoveRequest;
use App\Http\Requests\Products\StoreStockLocationProductsRequest;
use App\Http\Requests\Products\StoreStockTransferRequest;
use App\Http\Requests\Products\UpdateStockLocationProductsRequest;

class StockLocationProductsController extends Controller
{

    protected $stockCalculationService;    
    protected $stockService;
    protected $batchService;

    public function __construct(StockCalculationService $stockCalculationService,
                                StockService $stockService,
                                BatchService $batchService)
    {
        $this->stockCalculationService = $stockCalculationService;
        $this->stockService = $stockService;
        $this->batchService = $batchService;
    }
    
    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        
        $StockMoves = StockMove::with('batch')->where('stock_location_products_id', $id)->orderby('created_at', 'desc')->get();
        $StockLocationProduct = StockLocationProducts::findOrFail($id);
        $Product = Products::findOrFail($StockLocationProduct->products_id);
        $StockLocation = StockLocation::findOrFail($StockLocationProduct->stock_locations_id);
        $Stock = Stocks::findOrFail($StockLocation->stocks_id);
        // Tâches et lignes de commande de l'article, pas de la ligne de stock : $id est l'id de
        // la ligne de stock, et le formulaire de sortie proposait des documents sans rapport.
        $TaskList = Task::where('component_id', $StockLocationProduct->products_id)
                        ->whereNotNull('order_lines_id')
                        ->orderby('created_at', 'desc')->get();
        $OrderLineList = OrderLines::where('product_id', $StockLocationProduct->products_id)
                        ->orderby('created_at', 'desc')->get();

        $averageCost = $this->stockCalculationService->calculateWeightedAverageCost($id);

        $AllStockLocations = StockLocation::where('id', '!=', $StockLocationProduct->stock_locations_id)
                                            ->orderBy('label')
                                            ->get();

        $Pieces = app(SheetRemnants::class)->pieces($StockLocationProduct);
        // Tôle (ou toute pièce à deux dimensions) : la sortie propose de remettre la chute en stock.
        $handlesRemnants = ($Product->x_size > 0 && $Product->y_size > 0)
            || collect($Pieces)->contains(fn ($p) => $p['format'] !== null);

        return view('products/StockLocationProduct-show', [
            'Stock' => $Stock,
            'StockLocation' => $StockLocation,
            'StockLocationProduct' => $StockLocationProduct,
            'Product' => $Product,
            'StockMoves' => $StockMoves,
            'TaskList' => $TaskList,
            'OrderLineList' => $OrderLineList,
            'averageCost' => $averageCost,
            'AllStockLocations' => $AllStockLocations,
            'Pieces' => $Pieces,
            'handlesRemnants' => $handlesRemnants,
        ]);
    }

    public function store(StoreStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::create($request->only('code',
                                                                'user_id', 
                                                                'stock_locations_id',
                                                                'products_id', 
                                                                'mini_qty',
                                                                'end_date',
                                                                'addressing',
                                            ));
        return redirect()->route('products.stocklocation.show', ['id' => $StockLocationProduct->stock_locations_id])->with('success', __('general_content.stock_line_created_success_trans_key'));
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::find($request->id);
        $StockLocationProduct->mini_qty=$request->mini_qty;
        $StockLocationProduct->user_id=$request->user_id;
        $StockLocationProduct->end_date=$request->end_date;
        $StockLocationProduct->addressing=$request->addressing;
        $StockLocationProduct->save();
        return redirect()->route('products.stocklocation.show', ['id' => $request->stock_locations_id])->with('success', 'Successfully updated stock line'.  $StockLocationProduct->label);
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromInternalOrder(StoreStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::create($request->only('code',
                                                                'user_id', 
                                                                'stock_locations_id',
                                                                'products_id', 
                                                                'mini_qty',
                                                                'end_date',
                                                                'addressing',
                                            ));

        $product = Products::find($StockLocationProduct->products_id);
        if ($product->tracability_type == 2) {
            $tracability = $this->stockService->generateBatchNumber();
        } else {
            $tracability = null;
        }

        $data = [
            'user_id' => $request->user_id,
            'qty' => $request->mini_qty,
            'stock_location_products_id' => $StockLocationProduct->id,
            'order_line_id' => $request->order_line_id,
            'typ_move' => 12,
            'component_price' => $request->component_price,
            'x_size' => $product->x_size,
            'y_size' => $product->y_size,
            'z_size' => $product->z_size,
            'tracability' => $tracability,
        ];

        $this->stockService->createStockMove($data);

        // Mise à jour de la ligne de commande
        $this->stockService->updateOrderLine($request->order_line_id, $request->mini_qty);

        return redirect()->route('products.stockline.show', ['id' => $StockLocationProduct->id])->with('success', __('general_content.stock_move_created_success_trans_key'));
    }
    
    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entryFromInternalOrder(StoreStockMoveRequest $request)
    {
        $data = $request->only('user_id', 'qty', 'stock_location_products_id', 'order_line_id', 'typ_move', 'component_price');
        $this->stockService->createStockMove($data);

        // Mise à jour de la ligne de commande
        $this->stockService->updateOrderLine($request->order_line_id, $request->qty);

        return redirect()->route('products.stockline.show', ['id' => $request->stock_location_products_id])->with('success', __('general_content.stock_move_created_success_trans_key'));
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromPurchaseOrder(StoreStockLocationProductsRequest $request)
    {
        $lotData = $request->validate(BatchService::receiptRules());
        $purchaseReceiptLine = PurchaseReceiptLines::findOrFail($request->purchase_receipt_line_id);
        $receivedQty = $this->resolveAcceptedQty($purchaseReceiptLine);

        [$StockLocationProduct, $batch] = DB::transaction(function () use ($request, $purchaseReceiptLine, $receivedQty, $lotData) {
            $StockLocationProduct = StockLocationProducts::create($request->only('code',
                                                                    'user_id',
                                                                    'stock_locations_id',
                                                                    'products_id',
                                                                    'mini_qty',
                                                                    'end_date',
                                                                    'addressing',
                                                ));

            $product = Products::find($StockLocationProduct->products_id);
            // Lot créé si l'article est suivi par lot (comme le n° BATCH-… d'avant,
            // qui devient son code) ou si une coulée est saisie.
            $batch = $this->receiptBatch($purchaseReceiptLine, $product, $lotData);
            $tracability = $batch?->code;

            $data = [
                'user_id' => $request->user_id,
                'qty' => $receivedQty,
                'stock_location_products_id' => $StockLocationProduct->id,
                'task_id' => $request->task_id,
                'purchase_receipt_line_id' => $request->purchase_receipt_line_id,
                'batch_id' => $batch?->id,
                'typ_move' => 3,
                'component_price' => $request->component_price,
                'x_size' => $product->x_size,
                'y_size' => $product->y_size,
                'z_size' => $product->z_size,
                'tracability' => $tracability,
            ];

            $this->stockService->createStockMove($data);

            // Mise à jour de la ligne de réception de l'achat
            $this->stockService->updatePurchaseReceiptLine($request->purchase_receipt_line_id, $StockLocationProduct->id);

            return [$StockLocationProduct, $batch];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'stock_location_products_id' => $StockLocationProduct->id,
                'stock_code'                 => $StockLocationProduct->code,
                'stock_url'                  => route('products.stockline.show', ['id' => $StockLocationProduct->id]),
                'batch'                      => $this->batchPayload($batch),
            ]);
        }

        return redirect()->route('products.stockline.show', ['id' => $StockLocationProduct->id])->with('success', __('general_content.stock_move_created_success_trans_key'));
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entryFromPurchaseOrder(StoreStockMoveRequest $request)
    {
        $lotData = $request->validate(BatchService::receiptRules());
        $purchaseReceiptLine = PurchaseReceiptLines::findOrFail($request->purchase_receipt_line_id);
        $receivedQty = $this->resolveAcceptedQty($purchaseReceiptLine);
        $slp = StockLocationProducts::find($request->stock_location_products_id);

        $batch = DB::transaction(function () use ($request, $purchaseReceiptLine, $receivedQty, $lotData, $slp) {
            $data = $request->only('user_id', 'stock_location_products_id', 'task_id', 'purchase_receipt_line_id', 'typ_move', 'component_price');
            $data['qty'] = $receivedQty;

            $batch = $this->receiptBatch($purchaseReceiptLine, $slp ? Products::find($slp->products_id) : null, $lotData);
            if ($batch) {
                $data['batch_id'] = $batch->id;
                $data['tracability'] = $batch->code;
            }

            $this->stockService->createStockMove($data);

            // Mise à jour de la ligne de réception de l'achat
            $this->stockService->updatePurchaseReceiptLine($request->purchase_receipt_line_id, $request->stock_location_products_id);

            return $batch;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'stock_location_products_id' => (int) $request->stock_location_products_id,
                'stock_code'                 => $slp?->code,
                'stock_url'                  => route('products.stockline.show', ['id' => $request->stock_location_products_id]),
                'batch'                      => $this->batchPayload($batch),
            ]);
        }

        return redirect()->route('products.stockline.show', ['id' => $request->stock_location_products_id])->with('success', __('general_content.stock_move_created_success_trans_key'));
   }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entry(StoreStockMoveRequest $request)
    {
        $data = [
            'user_id' => $request->user_id,
            'qty' => $request->qty,
            'stock_location_products_id' => $request->stock_location_products_id,
            'typ_move' => $request->typ_move,
            'x_size' => $request->x_size,
            'y_size' => $request->y_size,
            'z_size' => $request->z_size,
            'surface_perc' => $request->surface_perc,
            'tracability' => $request->tracability,
        ];

        $stockMove = $this->stockService->createStockMove($data);
        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', __('general_content.stock_move_created_success_trans_key'));
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function sorting(StoreStockMoveRequest $request)
    {
        $stockLocationProduct = StockLocationProducts::find($request->stock_location_products_id);

        // Pièce choisie (tôle entière, chute) : sortie de cette pièce et remise en
        // stock de ses chutes. Sans pièce choisie, la sortie d'avant ci-dessous.
        if ($request->filled('piece') || $request->filled('remnants')) {
            return $this->sortPiece($request, $stockLocationProduct);
        }

        // Vérifie si la quantité demandée est disponible avec la traçabilité
        if ($this->stockCalculationService->canDispatch($request->tracability, $request->qty, $stockLocationProduct->id)) {
            $data = [
                'user_id' => $request->user_id,
                'qty' => $request->qty,
                'stock_location_products_id' => $request->stock_location_products_id,
                'typ_move' => $request->typ_move,
                'order_line_id' => $request->order_line_id,
                'task_id' => $request->task_id,
                'tracability' => $request->tracability,
            ];

            $stockMove = $this->stockService->createStockMove($data);

            return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', __('general_content.stock_move_created_success_trans_key'));
        }
        else{
            return redirect()->route('products.stockline.show', ['id' => $request->stock_location_products_id])->with('error', 'Not enough stock available for this tracability');
        }
    }

    private function sortPiece(StoreStockMoveRequest $request, StockLocationProducts $stockLocationProduct)
    {
        $back = redirect()->route('products.stockline.show', ['id' => $stockLocationProduct->id]);
        $remnants = app(SheetRemnants::class);

        try {
            $rows = $remnants->normalize($request->input('remnants'));
            // Une chute sans pièce choisie ne saurait d'où elle vient.
            if (! $request->filled('piece')) {
                if ($rows !== []) {
                    throw new \DomainException(__('stock_remnants.piece_required'));
                }

                // Lignes de chute laissées vides : sortie d'avant.
                $request->request->remove('remnants');

                return $this->sorting($request);
            }

            // Le formulaire envoie « null » en texte quand rien n'est choisi.
            $nullable = fn ($v) => is_numeric($v) ? (int) $v : null;

            $result = $remnants->dispatchPiece($stockLocationProduct, (string) $request->piece, (float) $request->qty, $rows, [
                'user_id'       => $request->user_id,
                'typ_move'      => $request->typ_move,
                'order_line_id' => $nullable($request->order_line_id),
                'task_id'       => $nullable($request->task_id),
            ]);
        } catch (\DomainException $e) {
            return $back->with('error', $e->getMessage());
        }

        $message = __('general_content.stock_move_created_success_trans_key');
        if ($result['remnants'] !== []) {
            $message .= ' ' . __('stock_remnants.remnants_created', ['count' => count($result['remnants'])]);
        }

        return $back->with('success', $message);
    }

    /**
     * Transfer stock from the current line to another stock location.
     */
    public function transfer(StoreStockTransferRequest $request)
    {
        try {
            $destination = $this->stockService->transfer(
                (int) $request->source_stock_location_products_id,
                (int) $request->destination_stock_locations_id,
                (float) $request->qty,
                $request->tracability ?: null,
                (int) $request->user_id,
            );

            return redirect()
                ->route('products.stockline.show', ['id' => $request->source_stock_location_products_id])
                ->with('success', __('general_content.transfer_success_trans_key'));
        } catch (\Exception $e) {
            Log::warning('Stock transfer failed', ['error' => $e->getMessage(), 'request' => $request->all()]);

            return redirect()
                ->route('products.stockline.show', ['id' => $request->source_stock_location_products_id])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Lot créé à la mise en stock d'une ligne de réception, ou null si l'article
     * n'est pas suivi par lot et qu'aucune coulée n'est saisie.
     */
    private function receiptBatch(PurchaseReceiptLines $line, ?Products $product, array $lotData): ?Batch
    {
        if (! $product || ! $this->batchService->receiptNeedsBatch($product, $lotData)) {
            return null;
        }

        return $this->batchService->createFromReceiptLine($line, $product->id, $lotData);
    }

    private function batchPayload(?Batch $batch): ?array
    {
        return $batch ? [
            'id'                  => $batch->id,
            'code'                => $batch->code,
            'heat_number'         => $batch->heat_number,
            'supplier_batch_code' => $batch->supplier_batch_code,
            'certificate_type'    => $batch->certificate_type,
            'url'                 => route('products.batches.show', ['batch' => $batch->id]),
        ] : null;
    }

    private function resolveAcceptedQty(PurchaseReceiptLines $purchaseReceiptLine): int
    {
        if ($purchaseReceiptLine->accepted_qty === null) {
            return (int) $purchaseReceiptLine->receipt_qty;
        }

        if ($purchaseReceiptLine->accepted_qty === 0 && ! $this->hasInspection($purchaseReceiptLine)) {
            return (int) $purchaseReceiptLine->receipt_qty;
        }

        return (int) $purchaseReceiptLine->accepted_qty;
    }

    private function hasInspection(PurchaseReceiptLines $purchaseReceiptLine): bool
    {
        return $purchaseReceiptLine->inspected_by !== null
            || $purchaseReceiptLine->inspection_date !== null
            || $purchaseReceiptLine->inspection_result !== null
            || (int) $purchaseReceiptLine->rejected_qty > 0
            || $purchaseReceiptLine->quality_non_conformity_id !== null;
    }
}
