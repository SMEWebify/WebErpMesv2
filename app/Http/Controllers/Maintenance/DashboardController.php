<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Maintenance\MaintenancePlan;
use App\Models\Maintenance\WorkOrder;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.factory', 'permission:asset_manager']);
    }

    public function __invoke()
    {
        $kpis = [
            [
                'name'        => 'MTBF',
                'description' => 'Temps moyen entre pannes',
                'value'       => '—',
            ],
            [
                'name'        => 'MTTR',
                'description' => 'Temps moyen de réparation',
                'value'       => '—',
            ],
            [
                'name'        => 'Disponibilité',
                'description' => '% temps machine dispo',
                'value'       => '—',
            ],
            [
                'name'        => 'Coût maintenance',
                'description' => '€/machine / période',
                'value'       => '—',
            ],
            [
                'name'        => 'Préventif/curatif',
                'description' => 'Qualité maintenance',
                'value'       => '—',
            ],
        ];

        $workOrdersCount = WorkOrder::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $recentWorkOrders = WorkOrder::with(['asset', 'technician'])
            ->orderByDesc('requested_at')
            ->limit(10)
            ->get()
            ->map(fn($wo) => [
                'id'           => $wo->id,
                'title'        => $wo->title,
                'asset'        => $wo->asset?->name,
                'work_type'    => $wo->work_type,
                'priority'     => $wo->priority,
                'status'       => $wo->status,
                'scheduled_at' => $wo->scheduled_at,
            ])
            ->values()
            ->toArray();

        $maintenancePlansCount = MaintenancePlan::count();

        $endpoints = [
            'work_orders_index'       => route('gmao.work-orders.index'),
            'work_orders_create'      => route('gmao.work-orders.create'),
            'work_order_show'         => url('/gmao/work-orders'),
            'maintenance_plans_index' => route('gmao.maintenance-plans.index'),
        ];

        return view('gmao.dashboard', [
            'kpis'                  => $kpis,
            'workOrdersCount'       => $workOrdersCount,
            'recentWorkOrders'      => $recentWorkOrders,
            'maintenancePlansCount' => $maintenancePlansCount,
            'endpoints'             => $endpoints,
        ]);
    }
}
