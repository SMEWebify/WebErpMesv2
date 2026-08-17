<?php

namespace Database\Seeders;

use App\Models\Times\WorkShiftPattern;
use Illuminate\Database\Seeder;

/**
 * Régimes horaires standards. Aucun n'est marqué par défaut : tant que l'atelier
 * n'en choisit pas un, les horaires historiques (8h-18h, lundi-vendredi) restent
 * en vigueur et rien ne change dans les calculs.
 */
class WorkShiftPatternSeeder extends Seeder
{
    /** Lundi à vendredi. */
    private const WORKING_WEEK = [1, 2, 3, 4, 5];

    public function run(): void
    {
        $patterns = [
            [
                'code' => 'JOURNEE',
                'label' => 'Journée (08h00-18h00)',
                'color' => '#3c8dbc',
                'comment' => 'Horaires historiques : une seule plage continue, du lundi au vendredi.',
                'slots' => [['08:00:00', '18:00:00', 'Journée']],
            ],
            [
                'code' => '1X8',
                'label' => '1×8',
                'color' => '#00a65a',
                'comment' => 'Une équipe : 06h00-14h00.',
                'slots' => [['06:00:00', '14:00:00', 'Matin']],
            ],
            [
                'code' => '2X8',
                'label' => '2×8',
                'color' => '#f39c12',
                'comment' => 'Deux équipes : 06h00-14h00 et 14h00-22h00.',
                'slots' => [
                    ['06:00:00', '14:00:00', 'Matin'],
                    ['14:00:00', '22:00:00', 'Après-midi'],
                ],
            ],
            [
                'code' => '3X8',
                'label' => '3×8',
                'color' => '#dd4b39',
                'comment' => 'Trois équipes, la nuit franchissant minuit : 22h00-06h00.',
                'slots' => [
                    ['06:00:00', '14:00:00', 'Matin'],
                    ['14:00:00', '22:00:00', 'Après-midi'],
                    ['22:00:00', '06:00:00', 'Nuit'],
                ],
            ],
        ];

        foreach ($patterns as $definition) {
            $pattern = WorkShiftPattern::firstOrCreate(
                ['code' => $definition['code']],
                [
                    'label' => $definition['label'],
                    'is_default' => false,
                    'color' => $definition['color'],
                    'comment' => $definition['comment'],
                ]
            );

            if ($pattern->slots()->exists()) {
                continue;
            }

            foreach (self::WORKING_WEEK as $weekday) {
                foreach ($definition['slots'] as [$start, $end, $label]) {
                    $pattern->slots()->create([
                        'weekday' => $weekday,
                        'start_time' => $start,
                        'end_time' => $end,
                        'label' => $label,
                    ]);
                }
            }
        }

        $this->command?->info('Work shift patterns check');
    }
}
