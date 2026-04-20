<?php

namespace App\Http\Controllers\Workshop;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Planning\TaskResources;
use App\Models\Products\StockMove;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsRessources;

class WorkshopController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('workshop/workshop');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function taskLines()
    {
        $defaultStatusIds = Status::where('title', '!=', 'Finished')->pluck('id')->toArray();
        if (empty($defaultStatusIds)) {
            $defaultStatusIds = Status::pluck('id')->toArray();
        }

        $props = [
            'endpoints' => [
                'list'    => route('workshop.task.lines.json'),
                'task'    => route('production.task.statu.id', ['id' => '__ID__']),
                'product' => route('products.show', ['id' => '__ID__']),
                'order'   => route('orders.show', ['id' => '__ID__']),
            ],
            'services'          => MethodsServices::select('id', 'label')->orderBy('ordre')->get(),
            'statuses'          => Status::orderBy('order')->get(['id', 'title']),
            'resources'         => MethodsRessources::select('id', 'label')->orderBy('label')->get(),
            'default_status_ids' => $defaultStatusIds,
        ];

        return view('workshop/workshop-task-lines', compact('props'));
    }

    public function taskLinesJson(Request $request)
    {
        $search      = $request->get('search', '');
        $serviceId   = $request->get('service_id', '');
        $resourceId  = $request->get('resource_id', '');
        $statusIds   = $request->get('status_ids', []);
        $showGeneric = $request->boolean('show_generic', false);
        $sortField   = in_array($request->get('sort', 'end_date'), ['label', 'end_date', 'methods_services_id']) ? $request->get('sort', 'end_date') : 'end_date';
        $sortAsc     = $request->boolean('asc', true);
        $today       = Carbon::today()->format('Y-m-d');

        $query = Task::with([
            'OrderLines.order',
            'SubAssembly.OrderLines.order',
            'SubAssembly.Child',
            'Component',
            'service',
            'resources',
            'status',
        ]);

        if ($showGeneric) {
            $query->where(function ($q) use ($serviceId, $search, $resourceId, $statusIds) {
                $q->whereNull('quote_lines_id')
                  ->whereNull('order_lines_id')
                  ->whereNull('products_id')
                  ->whereNull('sub_assembly_id');
            })->orWhere(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('sub_assembly_id')
                          ->whereHas('SubAssembly', fn($sq) => $sq->whereNotNull('order_lines_id'));
                })->orWhereNotNull('order_lines_id');
            });
        } else {
            $query->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('sub_assembly_id')
                          ->whereHas('SubAssembly', fn($sq) => $sq->whereNotNull('order_lines_id'));
                })->orWhereNotNull('order_lines_id');
            });
        }

        $query->where('label', 'like', '%' . $search . '%')
              ->when($serviceId, fn($q) => $q->where('methods_services_id', $serviceId))
              ->when($resourceId, fn($q) => $q->whereHas('resources', fn($r) => $r->where('methods_ressources.id', $resourceId)))
              ->when($statusIds, fn($q) => $q->whereIn('status_id', $statusIds))
              ->orderBy($sortField, $sortAsc ? 'asc' : 'desc');

        $tasks = $query->get();

        return response()->json($tasks->map(function ($task) use ($today) {
            $endDate      = $task->getFormattedEndDateAttribute();
            $endDateClass = 'bg-primary';
            if ($task->type != 1 && $task->type != 7) {
                $endDateClass = 'bg-info';
            } elseif ($today > $endDate) {
                $endDateClass = 'bg-danger';
            } elseif ($today === $endDate) {
                $endDateClass = 'bg-orange';
            }

            return [
                'id'          => $task->id,
                'label'       => $task->label,
                'order'       => $task->SubAssembly?->order_lines_id
                    ? ['id' => $task->SubAssembly->OrderLines?->orders_id, 'code' => $task->SubAssembly->OrderLines?->order?->code]
                    : ($task->OrderLines ? ['id' => $task->OrderLines->orders_id, 'code' => $task->OrderLines->order?->code] : null),
                'qty'         => $task->SubAssembly
                    ? ($task->SubAssembly->qty . ' x')
                    : ($task->OrderLines ? $task->OrderLines->qty . ' x ' . $task->qty : null),
                'order_label' => $task->SubAssembly
                    ? $task->SubAssembly->Child?->label
                    : $task->OrderLines?->label,
                'component'   => $task->component_id ? [
                    'id'          => $task->component_id,
                    'label'       => $task->Component?->label,
                    'color_class' => $task->Component?->getColorStockStatu(),
                ] : null,
                'service'     => $task->methods_services_id ? [
                    'label'       => $task->service?->label,
                    'color'       => $task->service?->color,
                    'picture_url' => $task->service?->picture
                        ? asset('storage/images/methods/' . $task->service->picture)
                        : null,
                ] : null,
                'resources'   => $task->resources->map(fn($r) => [
                    'label'       => $r->label,
                    'picture_url' => $r->picture ? asset('/images/ressources/' . $r->picture) : null,
                ])->values(),
                'qty_required'   => number_format($task->getQualityRequiredAttribute(), 0, '', ' '),
                'seting_time'    => $task->seting_time,
                'unit_time'      => $task->unit_time,
                'total_time'     => $task->TotalTime(),
                'progress'       => $task->progress(),
                'status_title'   => $task->status?->title,
                'end_date'       => $endDate,
                'end_date_class' => $endDateClass,
                'service_label'  => $task->service?->label,
            ];
        }));
    }

    /**
     * Display the status of tasks in the workshop.
     *
     * This method calculates and returns various statistics related to tasks in the workshop,
     * including the number of tasks in different statuses, the average processing time of tasks,
     * user productivity, and resource allocation.
     *
     * @param \Illuminate\Http\Request $request The incoming request instance.
     * 
     * @return \Illuminate\View\View The view displaying the task status.
     *
     * Statistics returned:
     * - Number of tasks with status 'Open'
     * - Number of tasks with status 'In Progress'
     * - Number of tasks with status 'Pending'
     * - Number of tasks with status 'Supplied'
     * - Number of tasks with status 'Finished'
     * - Average processing time of tasks (in seconds)
     * - User productivity (number of tasks each user has worked on)
     * - Total number of resources allocated to tasks
     * - Total hours allocated to each resource
     */
    public function statu(Request $request)
    {
        // Number of current OFs
        $tasksOpen = Task::whereHas('status', function($query) {
            $query->where('title', 'Open');
        })->count();

        $tasksInProgress = Task::whereHas('status', function($query) {
            $query->where('title', 'In Progress');
        })->count();

        // État des OF
        $tasksPending = Task::whereHas('status', function($query) {
            $query->where('title', 'Pending');
        })->count();

        $tasksOngoing = Task::whereHas('status', function($query) {
            $query->where('title', 'Supplied');
        })->count();

        $tasksCompleted = Task::whereHas('status', function($query) {
            $query->where('title', 'Finished');
        })->count();

        // Calculation of the average OF processing time
        $averageProcessingTime = 0;
        $tasksWithEndDate = Task::whereNotNull('end_date')->get();
        if($tasksWithEndDate->count() > 0){
            $totalTime = $tasksWithEndDate->sum(function ($task) {
                return $task->getTotalLogTime() * 3600; //in second time
            });
            $averageProcessingTime = $totalTime / $tasksWithEndDate->count();
        }

        // User productivity
        $userProductivity = DB::table('task_activities')
            ->join('users', 'task_activities.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(task_activities.id) as tasks_count'))
            ->groupBy('users.name')
            ->get();

        //Ressources Time
        $totalResourcesAllocated = TaskResources::count();
        $tasks = Task::with('resources')->get();

        $resourceHours = [];
        
        foreach ($tasks as $task) {
            foreach ($task->resources as $resource) {
                $resourceName = $resource->label;
                $totalTime = $task->TotalTime();
        
                if (array_key_exists($resourceName, $resourceHours)) {
                    $resourceHours[$resourceName] += $totalTime;
                } else {
                    $resourceHours[$resourceName] = $totalTime;
                }
            }
        }

        return view('workshop/workshop-task-statu', compact(
                                                    'tasksOpen',
                                                    'tasksInProgress',
                                                    'tasksPending',
                                                    'tasksOngoing',
                                                    'tasksCompleted',
                                                    'averageProcessingTime',
                                                    'userProductivity',
                                                    'totalResourcesAllocated',
                                                    'resourceHours'
                                                    ), ['TaskId' => $request->id]);
    }

    /**
     * Display the stock detail view.
     *
     * This method handles the request to display the stock detail view for a specific stock item.
     * It retrieves the stock item ID from the request and passes it to the view.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance.
     * @return \Illuminate\View\View The view for the stock detail page.
     */
    public function stockDetail(Request $request)
    {
        $stockMove = StockMove::with([
            'UserManagement',
            'StockLocationProducts.product',
            'OrderLine.order',
            'Task',
            'purchaseReceiptLines.purchaseReceipt',
            'photos',
        ])->findOrFail($request->id);

        $factory = app('Factory');
        $user = auth()->user();

        $initial = [
            'id'              => $stockMove->id,
            'user_name'       => $stockMove->UserManagement?->name,
            'date'            => $stockMove->GetPrettyCreatedAttribute(),
            'qty'             => $stockMove->qty,
            'typ_move'        => $stockMove->typ_move,
            'formatted_price' => $stockMove->formatted_component_price,
            'x_size'          => $stockMove->x_size,
            'y_size'          => $stockMove->y_size,
            'z_size'          => $stockMove->z_size,
            'nb_part'         => $stockMove->nb_part,
            'surface_perc'    => $stockMove->surface_perc,
            'tracability'     => $stockMove->tracability,
            'product_code'    => $stockMove->StockLocationProducts?->product?->code,
            'order'           => $stockMove->order_line_id ? [
                'id'   => $stockMove->OrderLine?->order?->id,
                'code' => $stockMove->OrderLine?->order?->code,
                'url'  => $user->can('orders-menu')
                    ? route('orders.show', ['id' => $stockMove->OrderLine?->order?->id])
                    : null,
            ] : null,
            'task'            => $stockMove->task_id ? [
                'id'  => $stockMove->task_id,
                'url' => $user->can('scheduling-menu')
                    ? route('production.task.statu.id', ['id' => $stockMove->task_id])
                    : null,
            ] : null,
            'purchase_receipt' => $stockMove->purchase_receipt_line_id ? [
                'id'   => $stockMove->purchaseReceiptLines?->purchase_receipt_id,
                'code' => $stockMove->purchaseReceiptLines?->purchaseReceipt?->code,
                'url'  => $user->can('purchases-menu')
                    ? route('purchase.receipts.show', ['id' => $stockMove->purchaseReceiptLines?->purchase_receipt_id])
                    : null,
            ] : null,
            'barcode_base64' => \DNS1D::getBarcodePNG(
                strval($stockMove->id),
                $factory->task_barre_code ?? 'C128',
                4, 60, [1, 1, 1], true
            ),
            'photos'    => $stockMove->photos->map(fn($p) => [
                'name'               => $p->name,
                'original_file_name' => $p->original_file_name,
            ])->values()->toArray(),
            'trace_url' => $stockMove->tracability
                ? route('production.trace', ['serial' => $stockMove->tracability])
                : null,
        ];

        $props = [
            'initial'   => $initial,
            'endpoints' => [
                'update'      => route('products.stock.detail.update.json', ['id' => $stockMove->id]),
                'photo_store' => route('photo.store'),
            ],
        ];

        return view('workshop/workshop-stock-detail', compact('props'));
    }
    
}
