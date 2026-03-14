<?php

namespace App\Http\Controllers;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SpreadsheetDataController extends Controller
{
    protected function guard(): ?JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return null;
    }

    public function stock(string $reference): JsonResponse
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $product = Products::where('code', $reference)->first();
        $quantity = $product ? (float) $product->getTotalStockMove() : 0;

        return response()->json([
            'reference' => $reference,
            'quantity' => $quantity,
            'unit' => optional($product?->Unit)->label,
            'updated_at' => $product?->updated_at?->toDateTimeString(),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $status = $request->get('status', 'all');
        $query = Orders::with('companie')->latest();

        if ($status === 'open') {
            $query->whereNotIn('statu', [3, 4, 5]);
        } elseif ($status === 'closed') {
            $query->whereIn('statu', [3, 4, 5]);
        }

        $orders = $query->limit(100)->get()->map(function (Orders $order) {
            return [
                'id' => $order->id,
                'reference' => $order->code,
                'customer' => optional($order->companie)->label,
                'total' => (float) ($order->total_price ?? 0),
                'status' => $order->statu,
                'created_at' => optional($order->created_at)?->toDateTimeString(),
            ];
        });

        return response()->json($orders->values());
    }

    public function revenue(Request $request): JsonResponse
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $month = $request->get('month', now()->format('Y-m'));
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $exception) {
            $start = now()->startOfMonth();
            $month = $start->format('Y-m');
        }
        $end = $start->copy()->endOfMonth();

        $invoices = Invoices::whereBetween('created_at', [$start, $end])->get();
        $totalHt = $invoices->sum(fn (Invoices $invoice) => (float) $invoice->total_price);

        return response()->json([
            'month' => $month,
            'total_ht' => round($totalHt, 2),
            'total_ttc' => round($totalHt * 1.2, 2),
            'count' => $invoices->count(),
        ]);
    }

    public function productionKpis(): JsonResponse
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $tasks = Task::query();
        $inProgress = (clone $tasks)->whereNotIn('status_id', [3, 4])->count();
        $completedThisMonth = (clone $tasks)->whereIn('status_id', [3, 4])->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
        $lateOrders = Orders::whereNotIn('statu', [3, 4, 5])->whereDate('created_at', '<', now()->subDays(30))->count();
        $avgLeadTimeDays = (float) round(
            (clone $tasks)
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
                ->value('avg_days') ?? 0,
            2
        );

        return response()->json([
            'avg_lead_time_days' => $avgLeadTimeDays,
            'orders_in_progress' => $inProgress,
            'orders_completed_this_month' => $completedThisMonth,
            'late_orders' => $lateOrders,
        ]);
    }

    public function customers(): JsonResponse
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $customers = Companies::query()
            ->where('statu_customer', 1)
            ->select(['id', 'label as name', 'city'])
            ->limit(200)
            ->get();

        return response()->json($customers);
    }

    public function products(): JsonResponse
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $products = Products::query()
            ->with('Stock_location_product')
            ->limit(200)
            ->get()
            ->map(function (Products $product) {
                return [
                    'id' => $product->id,
                    'reference' => $product->code,
                    'name' => $product->label,
                    'stock_qty' => (float) $product->getTotalStockMove(),
                ];
            });

        return response()->json($products->values());
    }
}
