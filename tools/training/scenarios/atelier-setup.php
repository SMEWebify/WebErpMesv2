<?php
// Données du guide « Atelier et production », rejouées par atelier.mjs via `artisan tinker` :
// une commande [FORMATION] propre au guide (pour ne pas toucher aux commandes des autres
// parcours), une ligne neuve dont la gamme passe par un poste [FORMATION] à visa de
// libération, et une matière [FORMATION] en stock sous un lot avec coulée.
// Chaque passage crée une ligne neuve (tâches « Open », sans BPF) ; poste, article, lot
// et commande sont réutilisés.
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Batch;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Support\Str;

$open = Status::where('title', 'Open')->value('id');

// Poste de charge [FORMATION] à visa (jamais un poste existant).
$visa = MethodsServices::firstOrCreate(['code' => 'FORM-VISA'], [
    'label' => '[FORMATION] Contrôle final', 'ordre' => 90, 'type' => 1,
    'hourly_rate' => 50, 'margin' => 0, 'color' => '#6f42c1', 'is_nesting' => 0,
]);
$visa->requires_release = 1;
$visa->save();

// Matière [FORMATION] suivie par lot, en stock avec une coulée.
$product = Products::firstOrCreate(['code' => 'FORM-TOLE-S235-3'], [
    'label' => '[FORMATION] Tôle S235 ép. 3', 'methods_services_id' => 1, 'methods_units_id' => 3,
    'purchased' => 1, 'purchased_price' => 12, 'sold' => 0, 'selling_price' => 0, 'tracability_type' => 2,
    'material' => 'S235', 'thickness' => 3,
]);
$slp = StockLocationProducts::firstOrCreate(
    ['products_id' => $product->id, 'stock_locations_id' => 12],
    ['code' => 'FORM-TOLE-S235-3', 'user_id' => 1, 'mini_qty' => 0, 'unit_cost' => 12],
);
$batch = Batch::firstOrCreate(['code' => 'FORM-LOT-0001'], [
    'product_id' => $product->id, 'heat_number' => 'F-20581', 'certificate_type' => '3.1',
]);
$balance = (float) StockMove::where('stock_location_products_id', $slp->id)->selectRaw(
    'COALESCE(SUM(CASE WHEN typ_move IN (1,3,5,12,14) THEN qty WHEN typ_move IN (2,4,6,9,15) THEN -qty ELSE 0 END),0) AS b'
)->value('b');
if ($balance < 20) {
    StockMove::create(['user_id' => 1, 'qty' => 100, 'stock_location_products_id' => $slp->id,
        'batch_id' => $batch->id, 'tracability' => $batch->code, 'typ_move' => 3]);
}

// Commande [FORMATION] réservée au guide.
$order = Orders::where('label', '[FORMATION] Atelier')->first();
if (! $order) {
    $model = Orders::whereHas('companie', fn ($q) => $q->where('label', 'like', '[FORMATION]%'))->latest('id')->firstOrFail();
    $order = $model->replicate(['reviewed_by', 'reviewed_at', 'review_decision', 'review_gaps', 'review_checklist',
        'quotes_id', 'n2p_last_push_at', 'n2p_last_push_status', 'n2p_last_push_error', 'n2p_last_status_event_at']);
    $order->uuid = (string) Str::uuid();
    $order->code = 'OR-FORM-ATELIER';
    $order->label = '[FORMATION] Atelier';
    $order->statu = 1;
    $order->save();
}

$line = OrderLines::create([
    'orders_id' => $order->id, 'ordre' => (int) OrderLines::where('orders_id', $order->id)->max('ordre') + 1,
    'code' => 'FORM-CAPOT', 'label' => '[FORMATION] Capot tôlé S235 ép. 3', 'qty' => 10,
    'delivered_qty' => 0, 'delivered_remaining_qty' => 10, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 10,
    'methods_units_id' => 3, 'selling_price' => 85, 'discount' => 0, 'accounting_vats_id' => 3,
    'delivery_date' => now()->addDays(10)->toDateString(), 'internal_delay' => now()->addDays(7)->toDateString(),
    'tasks_status' => 1, 'delivery_status' => 1, 'invoice_status' => 1,
]);

$base = ['order_lines_id' => $line->id, 'status_id' => $open, 'priority' => 2, 'origin' => '1'];
// Échéances dans le mois : le planning de charge les montre (charge posée sur la date de fin).
$due = fn (int $days) => now()->addWeekdays($days)->setTime(16, 0);
$material = Task::create($base + ['code' => 'MAT', 'label' => 'MATERIAL', 'ordre' => 5, 'type' => 3,
    'methods_services_id' => 1, 'component_id' => $product->id, 'qty' => 1, 'unit_cost' => 12, 'unit_price' => 12]);
$cut = Task::create($base + ['code' => 'LASER', 'label' => 'Découpe laser', 'ordre' => 10, 'type' => 1,
    'methods_services_id' => 2, 'seting_time' => 0.25, 'unit_time' => 0.05, 'unit_cost' => 13, 'unit_price' => 40, 'end_date' => $due(2)]);
$check = Task::create($base + ['code' => 'FORM-VISA', 'label' => '[FORMATION] Contrôle final', 'ordre' => 20, 'type' => 1,
    'methods_services_id' => $visa->id, 'seting_time' => 0.1, 'unit_time' => 0.02, 'unit_cost' => 5, 'unit_price' => 10, 'end_date' => $due(3)]);
$pack = Task::create($base + ['code' => 'EMBA', 'label' => 'Emballage', 'ordre' => 30, 'type' => 1,
    'methods_services_id' => 11, 'seting_time' => 0.1, 'unit_time' => 0.01, 'unit_cost' => 3, 'unit_price' => 6, 'end_date' => $due(4)]);

// Seconde ligne laissée sans BPF (pastille « sans BPF » du planning de charge), réutilisée.
$waiting = OrderLines::where('orders_id', $order->id)->where('code', 'FORM-EQUERRE')
    ->whereNull('released_for_production_at')->first();
if (! $waiting) {
    $waiting = OrderLines::create([
        'orders_id' => $order->id, 'ordre' => (int) OrderLines::where('orders_id', $order->id)->max('ordre') + 1,
        'code' => 'FORM-EQUERRE', 'label' => '[FORMATION] Équerre de fixation', 'qty' => 40,
        'delivered_qty' => 0, 'delivered_remaining_qty' => 40, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 40,
        'methods_units_id' => 3, 'selling_price' => 6, 'discount' => 0, 'accounting_vats_id' => 3,
        'delivery_date' => now()->addDays(12)->toDateString(), 'internal_delay' => now()->addDays(9)->toDateString(),
        'tasks_status' => 1, 'delivery_status' => 1, 'invoice_status' => 1,
    ]);
    Task::create(['order_lines_id' => $waiting->id, 'status_id' => $open, 'priority' => 2, 'origin' => '1',
        'code' => 'LASER', 'label' => 'Découpe laser', 'ordre' => 10, 'type' => 1, 'methods_services_id' => 2,
        'seting_time' => 0.25, 'unit_time' => 0.05, 'unit_cost' => 13, 'unit_price' => 40]);
}
Task::where('order_lines_id', $waiting->id)->update(['end_date' => $due(2)]);

echo json_encode([
    'order' => $order->id, 'line' => $line->id, 'visa_service' => $visa->id,
    'material' => $material->id, 'cut' => $cut->id, 'check' => $check->id, 'pack' => $pack->id,
    'cut_service' => 2,
]);
