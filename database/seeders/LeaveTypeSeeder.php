<?php

namespace Database\Seeders;

use App\Models\HumanResources\LeaveType;
use Illuminate\Database\Seeder;

/**
 * Default leave natures. Only the ones flagged counts_against_balance consume
 * an entitlement; the others are tracked for reporting only.
 */
class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'CP',   'label' => 'Congés payés',          'color' => '#28a745', 'counts_against_balance' => true,  'default_annual_quota' => 25, 'ordre' => 1],
            ['code' => 'RTT',  'label' => 'RTT',                   'color' => '#17a2b8', 'counts_against_balance' => true,  'default_annual_quota' => 0,  'ordre' => 2],
            ['code' => 'REC',  'label' => 'Récupération',          'color' => '#6f42c1', 'counts_against_balance' => true,  'default_annual_quota' => 0,  'ordre' => 3],
            ['code' => 'MAL',  'label' => 'Arrêt maladie',         'color' => '#dc3545', 'counts_against_balance' => false, 'default_annual_quota' => 0,  'ordre' => 4],
            ['code' => 'FAM',  'label' => 'Événement familial',    'color' => '#fd7e14', 'counts_against_balance' => false, 'default_annual_quota' => 0,  'ordre' => 5],
            ['code' => 'FORM', 'label' => 'Formation',             'color' => '#007bff', 'counts_against_balance' => false, 'default_annual_quota' => 0,  'ordre' => 6],
            ['code' => 'SS',   'label' => 'Congé sans solde',      'color' => '#6c757d', 'counts_against_balance' => false, 'default_annual_quota' => 0,  'ordre' => 7],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
