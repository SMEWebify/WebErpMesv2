<?php

namespace App\Services;

use App\Models\Workflow\Orders;

class OrderBusinessBalanceService
{
    public function getBusinessBalance($order)
    {
        $orderLines = $order->orderLines; // Récupère toutes les lignes de commande
        $businessBalance = [];

        foreach ($orderLines as $line) {
            
            $tasks = $line->task; // Collection de tâches

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
        return $businessBalance;
    }

    public function getBusinessBalanceTotals($order)
    {
        $businessBalance = $this->getBusinessBalance($order);

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

        return $totals;
    }
}
