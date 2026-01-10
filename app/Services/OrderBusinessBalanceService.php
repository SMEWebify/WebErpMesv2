<?php

namespace App\Services;

use Illuminate\Support\Number;
use App\Models\Workflow\Orders;

class OrderBusinessBalanceService
{
    /**
     * Get the business balance for the given order.
     *
     * This function calculates the total hours, total cost, total price, realized hours,
     * realized cost, difference in hours, and difference in cost for each service in the order.
     *
     * @param Orders $order The order object containing order lines and tasks.
     * @return array The business balance details for each service.
     */
    public function getBusinessBalance($order)
    {
        $orderLines = $order->orderLines; // Retrieve all order lines
        $businessBalance = [];

        foreach ($orderLines as $line) {
            
            $tasks = $line->task; // Collection of tasks

            foreach ($tasks as $task) {
                $service = $task->service;
                $taskName = $service ? $service->label : 'Service non défini';

                if (!isset($businessBalance[$taskName])) {
                    $businessBalance[$taskName] = [
                        'total_hours' => $task->TotalTime(),
                        'total_cost' => $task->TotalCost(),
                        'total_price' => $task->TotalPrice(),
                        'realized_hours' => $task->getTotalLogTime(),
                        'realized_cost' => $task->getTotalRealizedCost(),
                        // Calcul de l'écart
                        'difference_hours' => $task->TotalTime()-$task->getTotalLogTime(),
                        'difference_cost' => $task->TotalCost()-0,
                    ];
                }
                else{
                        // Cumul des heures et coûts
                        $businessBalance[$taskName]['total_hours'] += $task->TotalTime();
                        $businessBalance[$taskName]['total_cost'] += $task->TotalCost();
                        $businessBalance[$taskName]['total_price'] += $task->TotalPrice();
                        $businessBalance[$taskName]['realized_hours'] += $task->getTotalLogTime();
                        $businessBalance[$taskName]['realized_cost'] += $task->getTotalRealizedCost();
                        // Calcul de l'écart
                        $businessBalance[$taskName]['difference_hours'] = $businessBalance[$taskName]['total_hours'] - $businessBalance[$taskName]['realized_hours'];
                        $businessBalance[$taskName]['difference_cost'] = $businessBalance[$taskName]['total_cost'] - $businessBalance[$taskName]['realized_cost'];
                }

            }
        }

        // Ajout des versions formatées AVANT de retourner le tableau
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        foreach ($businessBalance as $taskName => &$balance) {
            // Valeurs formatées (pour affichage)
            $balance['total_display_cost'] = Number::currency($balance['total_cost'], $currency, config('app.locale'));
            $balance['total_display_price'] = Number::currency($balance['total_price'], $currency, config('app.locale'));
            $balance['realized_display_cost'] = Number::currency($balance['realized_cost'], $currency, config('app.locale'));
            $balance['difference_display_cost'] = Number::currency($balance['difference_cost'], $currency, config('app.locale'));
        }

        return $businessBalance;
    }

    /**
     * Calculate and return the total business balance for a given order.
     *
     * This method aggregates various totals from the business balance details
     * of the provided order. The totals include:
     * - total_hours: Sum of all total hours.
     * - total_cost: Sum of all total costs.
     * - total_price: Sum of all total prices.
     * - realized_hours: Sum of all realized hours.
     * - realized_cost: Sum of all realized costs.
     * - difference_hours: Sum of all difference hours.
     * - difference_cost: Sum of all difference costs.
     *
     * @param array $order The order data for which the business balance is calculated.
     * @return array An associative array containing the aggregated totals.
     */
    public function getBusinessBalanceTotals($order)
    {
        $businessBalance = $this->getBusinessBalance($order);
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';

        $totals = [
            'total_hours' => 0,
            'total_cost' => 0,
            'total_price' => 0,
            'realized_hours' => 0,
            'realized_cost' => 0,
            'difference_hours' => 0,
            'difference_cost' => 0,
        ];

        foreach ($businessBalance as $details) {
            $totals['total_hours'] += $details['total_hours'];
            $totals['total_cost'] += $details['total_cost'];
            $totals['total_price'] += $details['total_price'];
            $totals['realized_hours'] += $details['realized_hours'];
            $totals['realized_cost'] += $details['realized_cost'];
            $totals['difference_hours'] += $details['difference_hours'];
            $totals['difference_cost'] += $details['difference_cost'];
        }

        // Ajouter une version formatée des valeurs monétaires
        $totals['total_display_cost'] = Number::currency($totals['total_cost'], $currency, config('app.locale'));
        $totals['total_display_price'] = Number::currency($totals['total_price'], $currency, config('app.locale'));
        $totals['realized_display_cost'] = Number::currency($totals['realized_cost'], $currency, config('app.locale'));
        $totals['difference_display_cost'] = Number::currency($totals['difference_cost'], $currency, config('app.locale'));

        return $totals;
    }
}
