<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;

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
                'name' => 'MTBF',
                'description' => 'Temps moyen entre pannes',
                'value' => '—',
            ],
            [
                'name' => 'MTTR',
                'description' => 'Temps moyen de réparation',
                'value' => '—',
            ],
            [
                'name' => 'Disponibilité',
                'description' => '% temps machine dispo',
                'value' => '—',
            ],
            [
                'name' => 'Coût maintenance',
                'description' => '€/machine / période',
                'value' => '—',
            ],
            [
                'name' => '% préventif vs curatif',
                'description' => 'Qualité maintenance',
                'value' => '—',
            ],
        ];

        return view('gmao.dashboard', [
            'kpis' => $kpis,
        ]);
    }
}
