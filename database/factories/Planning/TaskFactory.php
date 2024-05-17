<?php
namespace Database\Factories\Planning;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

     private $qty = '';

    public function definition()
    {
        // Déterminez aléatoirement quel type d'association utiliser
        $associationType = $this->faker->randomElement(['quote', 'order', 'product']);

        $ordre = 1;
        $quoteLineId = null;
        $orderLineId = null;
        $productId = null;

        if ($associationType === 'quote') {
            $quoteLineId = QuoteLines::all()->random()->id;
            $ordre = $this->getNextOrder('quote_lines_id', $quoteLineId);
        } elseif ($associationType === 'order') {
            $orderLineId = OrderLines::all()->random()->id;
            $ordre = $this->getNextOrder('order_lines_id', $orderLineId);
        } 

        $methodsService = MethodsServices::inRandomOrder()->first();
        $methodsUnit = MethodsUnits::inRandomOrder()->first();
        
        $this->qty = $this->faker->biasedNumberBetween($min = 1, $max = 2990);

        return [
            'label' => $methodsService->label,
            'ordre' => $ordre,
            'quote_lines_id' => $quoteLineId,
            'order_lines_id' => $orderLineId,
            'products_id' => $productId,
            'methods_services_id' => $methodsService->id,
            'seting_time' => $this->faker->randomFloat(2, 1, 100),
            'unit_time' => $this->faker->randomFloat(2, 1, 100),
            'status_id' => $this->faker->numberBetween($min = 1, $max = 2),
            'type' => $methodsService->type,
            'qty' => $this->qty,
            'qty_init' => $this->qty,
            'unit_cost' => $this->faker->randomFloat(2, 1, 1000),
            'unit_price' => $this->faker->randomFloat(2, 1, 1000),
            'methods_units_id' => $methodsUnit->id,
        ];
    }

    /**
     * Get the next order number for a given line type and ID.
     */
    private function getNextOrder($lineType, $lineId)
    {
        $maxOrder = Task::where($lineType, $lineId)->max('ordre');
        return $maxOrder ? $maxOrder + 10 : 10;
    }
}
