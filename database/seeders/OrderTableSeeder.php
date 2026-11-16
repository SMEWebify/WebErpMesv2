<?php

namespace Database\Seeders;

use App\Models\Workflow\Orders;
use Illuminate\Database\Seeder;

class OrderTableSeeder extends Seeder
{
    /**
     * Répartition des statuts de commande (0 et 5 non générés) :
     * 1 ouverte, 2 en cours, 4 livrée en partie, 3 livrée, 6 annulée.
     */
    private const STATUS_WEIGHTS = [1 => 30, 2 => 30, 4 => 10, 3 => 27, 6 => 3];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Closure de state() rattachée à la factory : le tirage passe par une variable.
        $pickStatus = fn (): int => $this->pickStatus();

        Orders::factory()
            ->count(500)
            ->state(function () use ($pickStatus) {
                $statu = $pickStatus();

                return [
                    'statu' => $statu,
                    // Une commande livrée a sa date derrière elle (alimente l'OTD).
                    'validity_date' => $statu === 3
                        ? fake()->dateTimeInInterval('-30 weeks', '+29 weeks')
                        : fake()->dateTimeInInterval('+1 week', '+40 weeks'),
                ];
            })
            ->create();
    }

    private function pickStatus(): int
    {
        $draw = random_int(1, array_sum(self::STATUS_WEIGHTS));

        foreach (self::STATUS_WEIGHTS as $statu => $weight) {
            $draw -= $weight;
            if ($draw <= 0) {
                return $statu;
            }
        }

        return 1;
    }
}
