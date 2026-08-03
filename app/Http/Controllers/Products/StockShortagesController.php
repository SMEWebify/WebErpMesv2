<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Products\Products;
use App\Models\Products\StockReservation;
use Illuminate\Http\JsonResponse;

/**
 * Vue "Statut du stock" — fusion de l'ancienne page Stock courant et de la
 * ventilation par tâche des réservations. Une ligne par produit : status
 * global (stock vs besoin) + expand vers la répartition tâche par tâche
 * pour les composants achetés qui portent des réservations actives.
 */
class StockShortagesController extends Controller
{
    public function index()
    {
        return view('products.stock-shortages-index');
    }

    public function json(): JsonResponse
    {
        $products = Products::with([
            'Stock_location_product',
            'undeliveredOrderLines',
            'unFinishedTaskLines.OrderLines',
        ])->get();

        // Réservations regroupées par produit, pour éviter N+1 sur les composants achetés.
        $reservationsByProduct = StockReservation::with([
                'task:id,code,label,end_date,order_lines_id',
                'task.OrderLines:id,orders_id',
                'task.OrderLines.order:id,code',
            ])
            ->where('status', StockReservation::STATUS_ACTIVE)
            ->get()
            ->groupBy('products_id');

        $rows = $products->map(function ($product) use ($reservationsByProduct) {
            $totalStock = $product->getTotalStockMove();
            $undelivered = $product->getTotalUndeliveredQtyWithoutTasksAttribute();
            $taskQty     = $product->getTotalUnFinishedTaskLinesQtyAttribute();
            $qtyNeed     = $undelivered + $taskQty;

            $statusColor = match (true) {
                $totalStock > $qtyNeed  => 'success',
                $totalStock < $qtyNeed  => 'danger',
                default                 => 'warning',
            };

            $locations = $product->Stock_location_product->map(function ($loc) {
                $current = $loc->getCurrentStockMove();
                $color = match (true) {
                    $current > $loc->mini_qty => 'success',
                    $current < $loc->mini_qty => 'danger',
                    default                   => 'warning',
                };
                return [
                    'id'            => $loc->id,
                    'code'          => $loc->code,
                    'url'           => route('products.stockline.show', ['id' => $loc->id]),
                    'current_stock' => $current,
                    'mini_qty'      => $loc->mini_qty,
                    'color'         => $color,
                ];
            })->values();

            $breakdown = [];
            $missingTotal  = 0.0;
            $reservedTotal = 0.0;
            if ((int) $product->purchased === 1 && isset($reservationsByProduct[$product->id])) {
                $items = $reservationsByProduct[$product->id]
                    ->sortBy(fn ($r) => $r->task?->end_date?->format('Y-m-d') ?? '9999-12-31')
                    ->values();
                foreach ($items as $r) {
                    $missingTotal  += (float) $r->qty_missing;
                    $reservedTotal += (float) $r->qty_reserved;
                    $breakdown[] = [
                        'task_id'    => $r->task_id,
                        'task_code'  => $r->task?->code,
                        'task_label' => $r->task?->label,
                        'end_date'   => $r->task?->end_date?->format('Y-m-d'),
                        'order_code' => $r->task?->OrderLines?->order?->code,
                        'requested'  => (float) $r->qty_requested,
                        'reserved'   => (float) $r->qty_reserved,
                        'missing'    => (float) $r->qty_missing,
                    ];
                }
            }

            return [
                'id'                 => $product->id,
                'code'               => $product->code,
                'label'              => $product->label,
                'purchased'          => (int) $product->purchased === 1,
                'product_url'        => route('products.show', ['id' => $product->id]),
                'total_stock_move'   => $totalStock,
                'qty_need'           => $qtyNeed,
                'undelivered_qty'    => $undelivered,
                'task_qty'           => $taskQty,
                'status_color'       => $statusColor,
                'locations'          => $locations,
                'reservation_breakdown' => $breakdown,
                'reserved_total'     => round($reservedTotal, 3),
                'available_qty'      => round($totalStock - $reservedTotal, 3),
                'missing_total'      => round($missingTotal, 3),
            ];
        })->values();

        $counts = [
            'success' => $rows->where('status_color', 'success')->count(),
            'warning' => $rows->where('status_color', 'warning')->count(),
            'danger'  => $rows->where('status_color', 'danger')->count(),
        ];
        $shortageRows = $rows->filter(fn ($r) => $r['missing_total'] > 0);

        return response()->json([
            'products'  => $rows,
            'endpoints' => [
                'store_order' => route('products.stock.json.store-order'),
                'task'        => url('/'.app()->getLocale().'/task'),
            ],
            'totals' => [
                'total_products'    => $rows->count(),
                'ok_count'          => $counts['success'],
                'warning_count'     => $counts['warning'],
                'danger_count'      => $counts['danger'],
                'shortage_products' => $shortageRows->count(),
                'shortage_tasks'    => $shortageRows->sum(fn ($r) => count($r['reservation_breakdown'])),
                'missing_qty'       => round($shortageRows->sum('missing_total'), 3),
            ],
        ]);
    }
}
