<?php

namespace App\Services;

use App\Models\Workflow\OrderLines;
use Carbon\Carbon;

class OrderLinesService
{
    /**
     * Récupère les commandes à livrer dans les 2 prochains jours à partir des lignes de commande.
     */
    public function getIncomingOrders($limit = 10)
    {
        return OrderLines::where([
                ['delivery_date', '>=', Carbon::now()->toDateString()],
                ['delivery_date', '<=', Carbon::now()->addDays(2)->toDateString()],
            ])
            ->where('delivery_status', '<', 3)
            ->whereHas('order', function ($q) {
                $q->whereNotIn('statu', [5, 6]);
            })
            ->groupBy('orders_id')
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Récupère le nombre de commandes à livrer sous 2 jours (excédant 4)
     */
    public function getIncomingOrdersCount()
    {
        $count = OrderLines::where([
                ['delivery_date', '>', Carbon::now()->toDateString()],
                ['delivery_date', '<', Carbon::now()->addDays(2)->toDateString()],
            ])
            ->where('delivery_status', '<', 3)
            ->whereHas('order', function ($q) {
                $q->whereNotIn('statu', [5, 6]);
            })
            ->groupBy('orders_id')
            ->count();

        return max($count - 4, 0);
    }

    /**
     * Récupère les commandes en retard à partir des lignes de commande.
     */
    public function getLateOrders($limit = 10)
    {
        return OrderLines::where('delivery_date', '<', Carbon::now()->toDateString())
            ->where('delivery_status', '<', 3)
            ->whereHas('order', function ($q) {
                $q->whereNotIn('statu', [5, 6]);
            })
            ->groupBy('orders_id')
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Récupère le nombre de commandes en retard (excédant 4)
     */
    public function getLateOrdersCount()
    {
        $count = OrderLines::where('delivery_date', '<', Carbon::now()->toDateString())
            ->where('delivery_status', '<', 3)
            ->whereHas('order', function ($q) {
                $q->whereNotIn('statu', [5, 6]);
            })
            ->groupBy('orders_id')
            ->count();

        return max($count - 4, 0);
    }

    /**
     * Récupère les commandes prêtes à partir des lignes de commande.
     */
    public function getReadyOrders($limit = 10)
    {
        return OrderLines::where('tasks_status', '=', 4)
            ->groupBy('orders_id')
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }
}
