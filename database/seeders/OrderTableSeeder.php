<?php

namespace Database\Seeders;

use App\Models\Workflow\Orders;
use Carbon\Carbon;
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
        $deliveryDate = fn (int $statu): Carbon => $this->deliveryDate($statu);

        Orders::factory()
            ->count(500)
            ->state(function () use ($pickStatus, $deliveryDate) {
                $statu = $pickStatus();

                return [
                    'statu' => $statu,
                    'validity_date' => $deliveryDate($statu),
                ];
            })
            ->create();
    }

    /**
     * Date de livraison, en jours ouvrés : une commande livrée l'a derrière elle
     * (alimente l'OTD) ; une commande en cours ou ouverte tombe le plus souvent
     * dans les 4 semaines, pour charger le planning des 3 prochaines semaines.
     */
    private function deliveryDate(int $statu): Carbon
    {
        $soonShare = match ($statu) {
            3 => null,
            2, 4 => 80,
            default => 55,
        };

        if ($soonShare === null) {
            return Carbon::today()->subWeekdays(random_int(5, 150))->setTime(17, 0);
        }

        $days = random_int(1, 100) <= $soonShare ? random_int(2, 20) : random_int(21, 60);

        return Carbon::today()->addWeekdays($days)->setTime(17, 0);
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
