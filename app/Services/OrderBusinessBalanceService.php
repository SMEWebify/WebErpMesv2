<?php

namespace App\Services;

use Illuminate\Support\Number;
use App\Models\Workflow\Orders;

class OrderBusinessBalanceService
{
    // typ_move = 2 : stock allocation to a task (consumption)
    private const STOCK_MOVE_CONSUMPTION = 2;

    /**
     * Get the business balance for the given order, grouped by service.
     *
     * Per service:
     *   - total_hours / total_cost / total_price       → planned (from tasks)
     *   - realized_hours / realized_cost               → labour logged (time × hourly rate)
     *   - realized_purchase_cost                       → purchases received (receipt_qty × price)
     *   - realized_stock_cost                          → stock consumed (typ_move=2, qty × component_price)
     *   - realized_price                               → invoiced amount (prorated by task price share)
     *   - difference_hours / difference_cost           → planned − (labour + purchases + stock)
     */
    public function getBusinessBalance(Orders $order): array
    {
        $orderLines = $order->orderLines()->with([
            'task.service',
            'task.purchaseLines',
            'task.StockMove' => fn ($q) => $q->where('typ_move', self::STOCK_MOVE_CONSUMPTION),
            'invoiceLines',
        ])->get();

        $businessBalance = [];

        // Track (line_id → service → task price) for invoice proration
        $lineServiceTaskPrice = [];
        $lineTotalTaskPrice   = [];

        foreach ($orderLines as $line) {
            foreach ($line->task as $task) {
                $service = $task->service;
                $svcName = $service ? $service->label : 'Service non défini';

                $totalTime     = $task->TotalTime();
                $totalCost     = $task->TotalCost();
                $totalPrice    = $task->TotalPrice();
                $realizedHours = $task->getTotalLogTime();
                $realizedCost  = $task->getTotalRealizedCost();

                // Realized purchase cost (received qty from PurchaseLines)
                $purchaseCost = 0.0;
                foreach ($task->purchaseLines as $pl) {
                    $qty      = (float) ($pl->receipt_qty ?? 0);
                    $price    = (float) ($pl->selling_price ?? 0);
                    $discount = (float) ($pl->discount ?? 0);
                    $purchaseCost += round($qty * $price * (1 - $discount / 100), 2);
                }

                // Realized stock cost (typ_move=2 stock moves)
                $stockCost = 0.0;
                foreach ($task->StockMove as $move) {
                    $stockCost += round(
                        (float) ($move->qty ?? 0) * (float) ($move->component_price ?? 0),
                        2
                    );
                }

                if (!isset($businessBalance[$svcName])) {
                    $businessBalance[$svcName] = [
                        'total_hours'            => $totalTime,
                        'total_cost'             => $totalCost,
                        'total_price'            => $totalPrice,
                        'realized_hours'         => $realizedHours,
                        'realized_cost'          => $realizedCost,
                        'realized_purchase_cost' => $purchaseCost,
                        'realized_stock_cost'    => $stockCost,
                        'realized_price'         => 0.0,
                        'difference_hours'       => $totalTime - $realizedHours,
                        'difference_cost'        => $totalCost - ($realizedCost + $purchaseCost + $stockCost),
                    ];
                } else {
                    $businessBalance[$svcName]['total_hours']            += $totalTime;
                    $businessBalance[$svcName]['total_cost']             += $totalCost;
                    $businessBalance[$svcName]['total_price']            += $totalPrice;
                    $businessBalance[$svcName]['realized_hours']         += $realizedHours;
                    $businessBalance[$svcName]['realized_cost']          += $realizedCost;
                    $businessBalance[$svcName]['realized_purchase_cost'] += $purchaseCost;
                    $businessBalance[$svcName]['realized_stock_cost']    += $stockCost;
                    $businessBalance[$svcName]['difference_hours']       =
                        $businessBalance[$svcName]['total_hours'] - $businessBalance[$svcName]['realized_hours'];
                    $businessBalance[$svcName]['difference_cost']        =
                        $businessBalance[$svcName]['total_cost']
                        - ($businessBalance[$svcName]['realized_cost']
                            + $businessBalance[$svcName]['realized_purchase_cost']
                            + $businessBalance[$svcName]['realized_stock_cost']);
                }

                $lineServiceTaskPrice[$line->id][$svcName] =
                    ($lineServiceTaskPrice[$line->id][$svcName] ?? 0.0) + $totalPrice;
                $lineTotalTaskPrice[$line->id] =
                    ($lineTotalTaskPrice[$line->id] ?? 0.0) + $totalPrice;
            }
        }

        // Second pass: distribute invoiced amount across services by task price share
        foreach ($orderLines as $line) {
            if (empty($lineServiceTaskPrice[$line->id])) {
                continue;
            }

            $lineInvoiced = 0.0;
            foreach ($line->invoiceLines as $invoiceLine) {
                $qty      = (float) ($invoiceLine->qty ?? 0);
                $price    = (float) ($line->selling_price ?? 0);
                $discount = (float) ($line->discount ?? 0);
                $lineInvoiced += $qty * $price * (1 - $discount / 100);
            }

            $lineTotal = $lineTotalTaskPrice[$line->id] ?? 0.0;

            foreach ($lineServiceTaskPrice[$line->id] as $svcName => $svcPrice) {
                if (!isset($businessBalance[$svcName])) {
                    continue;
                }
                $ratio = $lineTotal > 0 ? $svcPrice / $lineTotal : 0.0;
                $businessBalance[$svcName]['realized_price'] += round($ratio * $lineInvoiced, 2);
            }
        }

        // Add formatted display values
        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        foreach ($businessBalance as &$balance) {
            $balance['total_display_cost']             = Number::currency($balance['total_cost'],             $currency, $locale);
            $balance['total_display_price']            = Number::currency($balance['total_price'],            $currency, $locale);
            $balance['realized_display_cost']          = Number::currency($balance['realized_cost'],          $currency, $locale);
            $balance['realized_display_purchase_cost'] = Number::currency($balance['realized_purchase_cost'], $currency, $locale);
            $balance['realized_display_stock_cost']    = Number::currency($balance['realized_stock_cost'],    $currency, $locale);
            $balance['realized_display_price']         = Number::currency($balance['realized_price'],         $currency, $locale);
            $balance['difference_display_cost']        = Number::currency($balance['difference_cost'],        $currency, $locale);
        }
        unset($balance);

        return $businessBalance;
    }

    /**
     * Aggregate the per-service business balance into a single totals row.
     */
    public function getBusinessBalanceTotals(array $businessBalance): array
    {
        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        $totals = [
            'total_hours'            => 0,
            'total_cost'             => 0.0,
            'total_price'            => 0.0,
            'realized_hours'         => 0,
            'realized_cost'          => 0.0,
            'realized_purchase_cost' => 0.0,
            'realized_stock_cost'    => 0.0,
            'realized_price'         => 0.0,
            'difference_hours'       => 0,
            'difference_cost'        => 0.0,
        ];

        foreach ($businessBalance as $details) {
            $totals['total_hours']            += $details['total_hours'];
            $totals['total_cost']             += $details['total_cost'];
            $totals['total_price']            += $details['total_price'];
            $totals['realized_hours']         += $details['realized_hours'];
            $totals['realized_cost']          += $details['realized_cost'];
            $totals['realized_purchase_cost'] += $details['realized_purchase_cost'];
            $totals['realized_stock_cost']    += $details['realized_stock_cost'];
            $totals['realized_price']         += $details['realized_price'];
            $totals['difference_hours']       += $details['difference_hours'];
            $totals['difference_cost']        += $details['difference_cost'];
        }

        $totals['total_display_cost']             = Number::currency($totals['total_cost'],             $currency, $locale);
        $totals['total_display_price']             = Number::currency($totals['total_price'],            $currency, $locale);
        $totals['realized_display_cost']           = Number::currency($totals['realized_cost'],          $currency, $locale);
        $totals['realized_display_purchase_cost']  = Number::currency($totals['realized_purchase_cost'], $currency, $locale);
        $totals['realized_display_stock_cost']     = Number::currency($totals['realized_stock_cost'],    $currency, $locale);
        $totals['realized_display_price']          = Number::currency($totals['realized_price'],         $currency, $locale);
        $totals['difference_display_cost']         = Number::currency($totals['difference_cost'],        $currency, $locale);

        return $totals;
    }

    /**
     * Return a flat list of all stock consumptions (typ_move=2) for the order,
     * enriched with service name and task label for display in the detail card.
     */
    public function getStockConsumptionDetails(Orders $order): array
    {
        $orderLines = $order->orderLines()->with([
            'task.service',
            'task.StockMove' => fn ($q) => $q->where('typ_move', self::STOCK_MOVE_CONSUMPTION)
                                             ->with('StockLocationProducts.Product'),
        ])->get();

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        $rows = [];

        foreach ($orderLines as $line) {
            foreach ($line->task as $task) {
                $svcName = $task->service ? $task->service->label : 'Service non défini';

                foreach ($task->StockMove as $move) {
                    $product = $move->StockLocationProducts?->Product;
                    $qty     = (float) ($move->qty ?? 0);
                    $price   = (float) ($move->component_price ?? 0);
                    $total   = round($qty * $price, 2);

                    $rows[] = [
                        'service'       => $svcName,
                        'task_label'    => $task->label,
                        'product_code'  => $product?->code  ?? '—',
                        'product_label' => $product?->label ?? '—',
                        'qty'           => $qty,
                        'unit_cost'     => $price,
                        'total_cost'    => $total,
                        'tracability'   => $move->tracability ?? '—',
                        'date'          => $move->created_at,
                        'display_unit'  => Number::currency($price,  $currency, $locale),
                        'display_total' => Number::currency($total,  $currency, $locale),
                    ];
                }
            }
        }

        // Sort by service then date
        usort($rows, fn ($a, $b) =>
            [$a['service'], $a['date']] <=> [$b['service'], $b['date']]
        );

        return $rows;
    }
}
