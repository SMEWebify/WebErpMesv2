<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Services\TaskService;
use App\Models\Planning\TaskActivities;
use App\Models\Products\StockMove;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\SerialNumbers;
use App\Services\QualityNonConformityService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TaskStatuController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly QualityNonConformityService $qualityNonConformityService,
    ) {}

    // -------------------------------------------------------------------------
    // GET /production/Task/Statu/Api/{id}
    // -------------------------------------------------------------------------
    public function show(int $id)
    {
        $task = Task::with([
            'status',
            'service.Ressources',
            'resources',
            'OrderLines.order',
            'OrderLines.OrderLineDetails',
            'OrderLines.Product',
            'Component',
            'purchaseLines.purchase',
            'purchaseLines.purchaseReceiptLines',
            'StockMove',
            'taskActivities.user',
        ])->findOrFail($id);

        $previousTask = null;
        $nextTask     = null;

        if ($task->order_lines_id) {
            $previousTask = Task::where('order_lines_id', $task->order_lines_id)
                ->where('ordre', '<', $task->ordre)
                ->orderBy('ordre', 'desc')
                ->first(['id', 'ordre', 'label']);

            $nextTask = Task::where('order_lines_id', $task->order_lines_id)
                ->where('ordre', '>', $task->ordre)
                ->orderBy('ordre', 'asc')
                ->first(['id', 'ordre', 'label']);
        }

        $lastActivity    = TaskActivities::where('task_id', $id)->latest()->first(['id', 'type']);
        $serviceResources = $task->service
            ? $task->service->Ressources->map(fn ($r) => ['id' => $r->id, 'label' => $r->label])->values()
            : [];

        $selectedResourceId = null;
        $userforcedResource = false;

        if ($task->resources->isNotEmpty()) {
            $res = $task->resources->first();
            $selectedResourceId = $res->id;
            $userforcedResource = (bool) ($res->pivot->userforced_ressource ?? false);
        }

        $stockLocations = $task->component_id
            ? StockLocationProducts::where('products_id', $task->component_id)
                ->get(['id', 'products_id'])
                ->map(fn ($s) => ['id' => $s->id, 'current_stock' => $s->getCurrentStockMove()])
                ->values()
            : [];

        $timeline = $this->buildTimeline($task);

        $orderLines = null;
        if ($task->OrderLines) {
            $ol = $task->OrderLines;
            $orderLines = [
                'id'        => $ol->id,
                'orders_id' => $ol->orders_id,
                'label'     => $ol->label,
                'qty'       => $ol->qty,
                'order'     => $ol->order ? ['id' => $ol->order->id, 'code' => $ol->order->code] : null,
                'picture'   => $ol->OrderLineDetails?->picture,
                'product'   => $ol->product_id ? [
                    'id'           => $ol->Product->id,
                    'label'        => $ol->Product->label,
                    'drawing_file' => $ol->Product->drawing_file,
                ] : null,
            ];
        }

        return response()->json([
            'id'                  => $task->id,
            'label'               => $task->label,
            'ordre'               => $task->ordre,
            'type'                => $task->type,
            'status_id'           => $task->status_id,
            'methods_services_id' => $task->methods_services_id,
            'order_lines_id'      => $task->order_lines_id,
            'component_id'        => $task->component_id,
            'seting_time'         => $task->seting_time,
            'unit_time'           => $task->unit_time,
            'qty'                 => $task->qty,
            'end_date'            => $task->end_date,
            'not_recalculate'     => (bool) $task->not_recalculate,
            // computed
            'total_log_time'      => $task->getTotalLogTime(),
            'total_log_good_qt'   => $task->getTotalLogGoodQt(),
            'total_log_bad_qt'    => $task->getTotalLogBadQt(),
            'total_net_good_qt'   => $task->getTotalNetGoodQt(),
            'trs'                 => $task->getTRSAttribute(),
            'progress'            => $task->progress(),
            'formatted_end_date'  => $task->getFormattedEndDateAttribute(),
            'formatted_unit_cost' => $task->getFormattedUnitCostAttribute(),
            'formatted_unit_price'=> $task->getFormattedUnitPriceAttribute(),
            'margin'              => $task->unit_cost > 0 ? $task->Margin() : null,
            'total_time'          => $task->TotalTime(),
            'order_qty'           => $task->GetOrderQtyLine(),
            // relations
            'status'              => $task->status ? ['id' => $task->status->id, 'title' => $task->status->title] : null,
            'service'             => $task->service ? [
                'id'      => $task->service->id,
                'label'   => $task->service->label,
                'type'    => $task->service->type,
                'picture' => $task->service->picture,
                'color'   => $task->service->color,
            ] : null,
            'resources'           => $task->resources->map(fn ($r) => [
                'id'                  => $r->id,
                'label'               => $r->label,
                'picture'             => $r->picture,
                'userforced_ressource'=> $r->pivot->userforced_ressource,
            ])->values(),
            'order_lines'         => $orderLines,
            'component'           => $task->Component ? [
                'id'           => $task->Component->id,
                'code'         => $task->Component->code,
                'label'        => $task->Component->label,
                'drawing_file' => $task->Component->drawing_file,
            ] : null,
            // navigation
            'previous_task'       => $previousTask,
            'next_task'           => $nextTask,
            // activity
            'last_activity_type'  => $lastActivity?->type,
            // resources
            'service_resources'   => $serviceResources,
            'selected_resource_id'=> $selectedResourceId,
            'userforced_resource' => $userforcedResource,
            // stock
            'stock_locations'     => $stockLocations,
            // timeline
            'timeline'            => $timeline,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/start
    // -------------------------------------------------------------------------
    public function start(int $id)
    {
        $this->taskService->recordTaskActivity($id, TaskActivities::TYPE_START, 0, 0);

        $status = Status::where('title', 'In progress')->first()
               ?? Status::where('title', 'In Progress')->first();

        if ($status) {
            Task::where('id', $id)->update(['status_id' => $status->id]);
            event(new \App\Events\TaskChangeStatu($id));
        }

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/pause
    // -------------------------------------------------------------------------
    public function pause(int $id)
    {
        $this->taskService->recordTaskActivity($id, TaskActivities::TYPE_END, 0, 0);
        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/finish
    // -------------------------------------------------------------------------
    public function finish(int $id)
    {
        $this->taskService->recordTaskActivity($id, TaskActivities::TYPE_FINISH, 0, 0);

        $status = Status::where('title', 'Finished')->first();
        if ($status) {
            Task::where('id', $id)->update(['status_id' => $status->id]);
            event(new \App\Events\TaskChangeStatu($id));
        }

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/good-qty
    // -------------------------------------------------------------------------
    public function goodQty(Request $request, int $id)
    {
        $request->validate(['qty' => 'required|numeric|min:0']);

        $this->taskService->recordTaskActivity($id, TaskActivities::TYPE_DECLARE_GOOD, $request->qty, 0);

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/good-qty-stock
    // -------------------------------------------------------------------------
    public function goodQtyStock(Request $request, int $id)
    {
        $request->validate([
            'qty'          => 'required|numeric|min:0',
            'component_id' => 'required|integer',
        ]);

        $qty              = (float) $request->qty;
        $componentId      = $request->component_id;
        $remaining        = $qty;

        foreach (StockLocationProducts::where('products_id', $componentId)->get() as $stock) {
            $toWithdraw = min($stock->getCurrentStockMove(), $remaining);
            if ($toWithdraw > 0) {
                StockMove::create([
                    'user_id'                    => Auth::id(),
                    'qty'                        => $toWithdraw,
                    'stock_location_products_id' => $stock->id,
                    'task_id'                    => $id,
                    'typ_move'                   => 2,
                ]);
            }
            $remaining -= $toWithdraw;
            if ($remaining <= 0) break;
        }

        $this->taskService->recordTaskActivity($id, TaskActivities::TYPE_DECLARE_GOOD, $qty, 0);

        return response()->json(['success' => true, 'negative_stock' => $remaining > 0]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/bad-qty
    // -------------------------------------------------------------------------
    public function badQty(Request $request, int $id)
    {
        $request->validate(['qty' => 'required|numeric|min:0']);

        $this->taskService->recordTaskActivity($id, TaskActivities::TYPE_DECLARE_BAD, 0, $request->qty);

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // PUT /production/Task/Statu/Api/{id}/date
    // -------------------------------------------------------------------------
    public function updateDate(Request $request, int $id)
    {
        $request->validate(['end_date' => 'required|date']);

        $notRecalculate = $request->boolean('not_recalculate') ? 1 : 0;

        Task::findOrFail($id)->fill([
            'not_recalculate' => $notRecalculate,
            'end_date'        => $request->end_date,
        ])->save();

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // PUT /production/Task/Statu/Api/{id}/resource
    // -------------------------------------------------------------------------
    public function updateResource(Request $request, int $id)
    {
        $request->validate(['resource_id' => 'required|exists:methods_ressources,id']);

        $task = Task::findOrFail($id);
        $task->resources()->sync([$request->resource_id => [
            'autoselected_ressource' => 0,
            'userforced_ressource'   => 1,
        ]]);

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /production/Task/Statu/Api/{id}/nc
    // -------------------------------------------------------------------------
    public function createNc(int $id)
    {
        $task = Task::with('OrderLines.order')->findOrFail($id);

        $companieId = $task->OrderLines?->order?->companies_id;
        $serviceId  = $task->methods_services_id;

        $this->qualityNonConformityService->createNC($id, $companieId, $serviceId, 'task');

        return response()->json([
            'success'      => true,
            'redirect_url' => route('quality.nonConformitie'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private: build timeline array sorted by datetime desc
    // -------------------------------------------------------------------------
    private function buildTimeline(Task $task): array
    {
        $timeline = [];

        foreach ($task->StockMove as $move) {
            [$icon, $content] = match ($move->typ_move) {
                3       => ['fas fa-list bg-primary', __('general_content.new_entry_stock_trans_key') . ' x' . $move->qty . ' - ' . __('general_content.purchase_order_reception_trans_key')],
                2, 6    => ['fas fa-list bg-warning', __('general_content.new_sorting_stock_trans_key') . ' x' . $move->qty . ' - ' . __('general_content.task_allocation_trans_key')],
                default => ['fas fa-list bg-secondary', 'Stock move x' . $move->qty],
            };

            $timeline[] = [
                'datetime_iso' => $move->created_at->toIso8601String(),
                'date_label'   => $move->created_at->format('d M Y'),
                'icon_class'   => $icon,
                'content'      => $content,
                'details'      => $move->created_at->format('d F Y - H:i:s'),
            ];
        }

        foreach ($task->taskActivities as $activity) {
            $userName = $activity->user?->name ?? 'System';

            [$icon, $content] = match ($activity->type) {
                TaskActivities::TYPE_START         => ['fas fa-play-circle bg-primary',   $userName . ' - ' . __('general_content.set_to_start_trans_key')],
                TaskActivities::TYPE_END           => ['fas fa-stop-circle bg-warning',   $userName . ' - ' . __('general_content.set_to_end_trans_key')],
                TaskActivities::TYPE_FINISH        => ['fas fa-check-circle bg-info',     $userName . ' - ' . __('general_content.set_to_finish_trans_key')],
                TaskActivities::TYPE_DECLARE_GOOD  => ['fas fa-thumbs-up bg-success',     $userName . ' - ' . __('general_content.declare_finish_trans_key') . ' ' . $activity->good_qt . ' ' . __('general_content.part_trans_key')],
                TaskActivities::TYPE_DECLARE_BAD   => ['fas fa-thumbs-down bg-danger',    $userName . ' - ' . __('general_content.declare_rejected_trans_key') . ' ' . $activity->bad_qt . ' ' . __('general_content.part_trans_key')],
                TaskActivities::TYPE_COMMENT       => ['fas fa-comment-dots bg-secondary', $userName . ' - ' . $activity->comment],
                default                            => ['fas fa-circle bg-secondary',       $userName],
            };

            $timeline[] = [
                'datetime_iso' => $activity->created_at->toIso8601String(),
                'date_label'   => $activity->created_at->format('d M Y'),
                'icon_class'   => $icon,
                'content'      => $content,
                'details'      => $activity->created_at->format('d F Y - H:i:s'),
            ];
        }

        foreach ($task->purchaseLines as $line) {
            $timeline[] = [
                'datetime_iso' => $line->created_at->toIso8601String(),
                'date_label'   => $line->created_at->format('d M Y'),
                'icon_class'   => 'fas fa-calendar-alt bg-success',
                'content'      => __('general_content.purchase_trans_key') . ' ' . ($line->purchase?->code ?? '?') . ' - ' . __('general_content.qty_reciept_trans_key') . ': ' . $line->receipt_qty . '/' . $line->qty,
                'details'      => $line->created_at->format('d F Y - H:i:s'),
            ];

            foreach ($line->purchaseReceiptLines as $receipt) {
                $timeline[] = [
                    'datetime_iso' => $receipt->created_at->toIso8601String(),
                    'date_label'   => $receipt->created_at->format('d M Y'),
                    'icon_class'   => 'fas fa-calendar-alt bg-warning',
                    'content'      => __('general_content.po_receipt_trans_key') . ' ' . $receipt->label . ' - ' . __('general_content.qty_reciept_trans_key') . ': ' . $receipt->receipt_qty,
                    'details'      => $receipt->created_at->format('d F Y - H:i:s'),
                ];
            }
        }

        $timeline[] = [
            'datetime_iso' => $task->created_at->toIso8601String(),
            'date_label'   => $task->created_at->format('d M Y'),
            'icon_class'   => 'fa fa-tags bg-primary',
            'content'      => 'Task créée',
            'details'      => $task->created_at->format('d F Y - H:i:s'),
        ];

        usort($timeline, fn ($a, $b) => strcmp($b['datetime_iso'], $a['datetime_iso']));

        return $timeline;
    }
}
