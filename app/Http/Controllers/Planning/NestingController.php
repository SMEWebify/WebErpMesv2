<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileKindResolver;
use Illuminate\Http\Request;

class NestingController extends Controller
{
    /** Stock move types counted as entries / exits (cf. StockReservationService). */
    private const STOCK_ENTRY_TYPES   = [1, 3, 5, 12, 14];
    private const STOCK_SORTING_TYPES = [2, 4, 6, 9];

    public function index()
    {
        return view('planning.nesting-index');
    }

    /**
     * Compute the material requirement from a fleet of open/launched orders.
     *
     * POST /nesting/compute
     * Body: { include_open: bool }
     *
     * Returns lines grouped by nesting service > (material, thickness), plus
     * two anomaly buckets (missing geometry / missing material-thickness).
     */
    /**
     * List of cutting services (methods_services.is_nesting = true) available
     * for filtering on the front-end.
     *
     * GET /nesting/services
     */
    public function services()
    {
        return response()->json(
            MethodsServices::where('is_nesting', true)
                ->orderBy('label')
                ->get(['id', 'label', 'color'])
        );
    }

    /**
     * Purchasable sheet-metal raw material products (family bound to a service
     * of type 3 = "Raw material (Sheet)") with their current on-hand stock.
     *
     * Used by the nesting page to reconcile the computed requirement against
     * what is already in stock.
     *
     * GET /nesting/sheet-stock
     */
    public function sheetStock()
    {
        $products = Products::query()
            ->join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
            ->join('methods_services', 'methods_services.id', '=', 'methods_families.methods_services_id')
            ->where('methods_services.type', 3)
            ->where('methods_families.nest_type', 'sheet')
            ->where('products.x_size', '>', 0)
            ->where('products.y_size', '>', 0)
            ->select(
                'products.id',
                'products.code',
                'products.label',
                'products.material',
                'products.thickness',
                'products.x_size',
                'products.y_size',
            )
            ->orderBy('products.material')
            ->orderBy('products.thickness')
            ->get();

        return response()->json($this->augmentWithStock($products, fn ($p) => [
            'id'        => $p->id,
            'code'      => $p->code,
            'label'     => $p->label,
            'material'  => trim((string) $p->material),
            'thickness' => (float) $p->thickness,
            'x_size'    => (float) $p->x_size,
            'y_size'    => (float) $p->y_size,
        ]));
    }

    /**
     * Purchasable bar / tube raw material products (methods_families.nest_type = 'bar').
     * Returns each stock item with its profile (y_size × z_size), standard length
     * (x_size), and on-hand + incoming quantities.
     *
     * GET /nesting/bar-stock
     */
    public function barStock()
    {
        $products = Products::query()
            ->join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
            ->where('methods_families.nest_type', 'bar')
            ->where('products.x_size', '>', 0)
            ->select(
                'products.id',
                'products.code',
                'products.label',
                'products.material',
                'products.thickness',
                'products.x_size',
                'products.y_size',
                'products.z_size',
            )
            ->orderBy('products.material')
            ->orderBy('products.y_size')
            ->orderBy('products.z_size')
            ->get();

        return response()->json($this->augmentWithStock($products, fn ($p) => [
            'id'        => $p->id,
            'code'      => $p->code,
            'label'     => $p->label,
            'material'  => trim((string) $p->material),
            'thickness' => (float) $p->thickness,
            'x_size'    => (float) $p->x_size,   // bar standard length
            'y_size'    => (float) $p->y_size,   // profile width / diameter
            'z_size'    => (float) $p->z_size,   // profile height (0 for round)
        ]));
    }

    /**
     * Attach stock_qty + incoming_qty to a Products collection using a single
     * aggregate query on stock_moves / purchase_lines. Shared by sheetStock()
     * and barStock() — both need the same "on-hand + pending PO" reconciliation.
     */
    private function augmentWithStock($products, callable $mapper)
    {
        $productIds = $products->pluck('id');
        if ($productIds->isEmpty()) return [];

        $incoming = \DB::table('purchase_lines')
            ->whereIn('product_id', $productIds->map(fn ($id) => (string) $id))
            ->whereColumn('receipt_qty', '<', 'qty')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(qty - receipt_qty) as pending_qty')
            ->pluck('pending_qty', 'product_id');

        $entryList   = implode(',', self::STOCK_ENTRY_TYPES);
        $sortingList = implode(',', self::STOCK_SORTING_TYPES);

        $onHand = \DB::table('stock_moves')
            ->join('stock_location_products', 'stock_moves.stock_location_products_id', '=', 'stock_location_products.id')
            ->whereIn('stock_location_products.products_id', $productIds)
            ->groupBy('stock_location_products.products_id')
            ->selectRaw("
                stock_location_products.products_id,
                COALESCE(SUM(CASE
                    WHEN stock_moves.typ_move IN ($entryList)   THEN stock_moves.qty
                    WHEN stock_moves.typ_move IN ($sortingList) THEN -stock_moves.qty
                    ELSE 0
                END), 0) AS stock_qty
            ")
            ->pluck('stock_qty', 'products_id');

        return $products->map(fn ($p) => $mapper($p) + [
            'stock_qty'    => (float) ($onHand[$p->id] ?? 0),
            'incoming_qty' => (float) ($incoming[$p->id] ?? 0),
        ]);
    }

    public function compute(Request $request)
    {
        $request->validate([
            'include_open' => 'sometimes|boolean',
            'service_ids'  => 'sometimes|array',
            'service_ids.*' => 'integer',
        ]);

        $includeOpen   = filter_var($request->input('include_open', false), FILTER_VALIDATE_BOOL);
        $allowedStatus = $includeOpen ? [1, 2] : [2];
        $serviceIds    = collect($request->input('service_ids', []))->map(fn ($v) => (int) $v)->filter()->values();

        $lines = OrderLines::query()
            ->whereHas('order', fn ($q) => $q->whereIn('statu', $allowedStatus))
            ->with([
                'Product:id,code,label,material,thickness',
                'order:id,code,statu',
                'OrderLineDetails',
            ])
            ->get(['id', 'orders_id', 'product_id', 'label', 'qty']);

        $lineIds    = $lines->pluck('id');
        $productIds = $lines->pluck('product_id')->filter()->unique()->values();

        // First is_nesting service task encountered on each line
        $nestingTasks = Task::query()
            ->whereIn('tasks.order_lines_id', $lineIds)
            ->join('methods_services', 'tasks.methods_services_id', '=', 'methods_services.id')
            ->where('methods_services.is_nesting', true)
            ->when($serviceIds->isNotEmpty(), fn ($q) => $q->whereIn('methods_services.id', $serviceIds))
            ->select(
                'tasks.order_lines_id',
                'tasks.methods_services_id',
                'methods_services.label as service_label',
                'methods_services.color as service_color',
            )
            ->orderBy('tasks.ordre')
            ->get()
            ->groupBy('order_lines_id')
            ->map->first();

        // Material component task (nest_type = sheet or bar) — carries material,
        // thickness, and the profile section (y/z) for bars.
        $materialTasks = Task::query()
            ->whereIn('tasks.order_lines_id', $lineIds)
            ->where('tasks.component_id', '>', 0)
            ->join('products', 'products.id', '=', 'tasks.component_id')
            ->join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
            ->whereIn('methods_families.nest_type', ['sheet', 'bar'])
            ->select(
                'tasks.order_lines_id',
                'methods_families.nest_type as nest_type',
                'products.material as mat_material',
                'products.thickness as mat_thickness',
                'products.y_size as mat_y_size',
                'products.z_size as mat_z_size',
            )
            ->get()
            ->groupBy('order_lines_id')
            ->map->first();

        // DXF/SVG files attached to products (priority 1). We look at the file
        // kind rather than the business role: a DXF is a DXF whether the user
        // classified it as "plan" or "vectoriel".
        $productFiles = $productIds->isEmpty() ? collect() : File::query()
            ->join('fileables', 'fileables.file_id', '=', 'files.id')
            ->whereIn('fileables.fileable_id', $productIds)
            ->where('fileables.fileable_type', Products::class)
            ->whereIn('files.kind', [FileKindResolver::KIND_CAD2D, FileKindResolver::KIND_VECTOR])
            ->select('files.*', 'fileables.fileable_id as _entity_id')
            ->get()
            ->groupBy('_entity_id')
            ->map->first();

        // DXF/SVG files attached to order lines (fallback)
        $lineFiles = $lineIds->isEmpty() ? collect() : File::query()
            ->join('fileables', 'fileables.file_id', '=', 'files.id')
            ->whereIn('fileables.fileable_id', $lineIds)
            ->where('fileables.fileable_type', OrderLines::class)
            ->whereIn('files.kind', [FileKindResolver::KIND_CAD2D, FileKindResolver::KIND_VECTOR])
            ->select('files.*', 'fileables.fileable_id as _entity_id')
            ->get()
            ->groupBy('_entity_id')
            ->map->first();

        $servicesMap     = [];
        $missingGeometry = [];
        $missingMaterial = [];
        $missingService  = [];

        foreach ($lines as $line) {
            $baseEntry = [
                'line_id'      => $line->id,
                'order_id'     => $line->orders_id,
                'order_code'   => $line->order?->code,
                'product_id'   => $line->product_id,
                'product_code' => $line->Product?->code,
                'label'        => $line->label ?: $line->Product?->label,
                'qty'          => (int) ($line->qty ?? 1),
            ];

            $nestingTask = $nestingTasks[$line->id] ?? null;
            if (!$nestingTask) {
                $missingService[] = $baseEntry;
                continue;
            }

            $entry = $baseEntry + [
                'service_id'    => $nestingTask->methods_services_id,
                'service_label' => $nestingTask->service_label,
            ];

            $details      = $line->OrderLineDetails;
            $matTask      = $materialTasks[$line->id] ?? null;
            $nestType     = $matTask?->nest_type ?? 'sheet';

            // Line details override the material task (the "detail-edit" screen
            // is the source of truth for the piece as fabricated).
            $matDetails   = trim((string) ($details?->material ?? ''));
            $thickDetails = (float) ($details?->thickness ?? 0);
            $material     = $matDetails !== '' ? $matDetails : trim((string) ($matTask?->mat_material ?? ''));
            $thickness    = $thickDetails > 0 ? $thickDetails : (float) ($matTask?->mat_thickness ?? 0);

            $sid = $nestingTask->methods_services_id;
            if (!isset($servicesMap[$sid])) {
                $servicesMap[$sid] = [
                    'service_id'    => $sid,
                    'service_label' => $nestingTask->service_label,
                    'service_color' => $nestingTask->service_color,
                    'groups'        => [],
                ];
            }

            if ($nestType === 'bar') {
                // Bar / tube — 1D packing. Length to cut = x_size on the details;
                // profile section (y/z) identifies which raw stock to consume.
                $length = (float) ($details?->x_size ?? 0);
                $yProf  = (float) ($details?->y_size ?? $matTask?->mat_y_size ?? 0);
                $zProf  = (float) ($details?->z_size ?? $matTask?->mat_z_size ?? 0);

                if ($material === '' || $yProf <= 0) {
                    $missingMaterial[] = $entry;
                    continue;
                }
                if ($length <= 0) {
                    $missingGeometry[] = $entry + [
                        'material'  => $material,
                        'thickness' => $thickness,
                    ];
                    continue;
                }

                $gkey = 'bar|' . $material . '|' . $yProf . '|' . $zProf;
                if (!isset($servicesMap[$sid]['groups'][$gkey])) {
                    $servicesMap[$sid]['groups'][$gkey] = [
                        'nest_type' => 'bar',
                        'material'  => $material,
                        'y_size'    => $yProf,
                        'z_size'    => $zProf,
                        'thickness' => $thickness,
                        'pieces'    => [],
                    ];
                }
                $servicesMap[$sid]['groups'][$gkey]['pieces'][] = $entry + [
                    'length' => $length,
                ];
                continue;
            }

            // Sheet — 2D bin packing, needs a bounding box (line dims or DXF).
            if ($material === '' || $thickness <= 0) {
                $missingMaterial[] = $entry;
                continue;
            }

            $file  = $lineFiles[$line->id] ?? $productFiles[$line->product_id] ?? null;
            $xLine = (float) ($details?->x_size ?? 0);
            $yLine = (float) ($details?->y_size ?? 0);
            $hasLineDims = $xLine > 0 && $yLine > 0;

            if (!$hasLineDims && !$file) {
                $missingGeometry[] = $entry + [
                    'material'  => $material,
                    'thickness' => $thickness,
                ];
                continue;
            }

            $gkey = 'sheet|' . $material . '|' . $thickness;
            if (!isset($servicesMap[$sid]['groups'][$gkey])) {
                $servicesMap[$sid]['groups'][$gkey] = [
                    'nest_type' => 'sheet',
                    'material'  => $material,
                    'thickness' => $thickness,
                    'pieces'    => [],
                ];
            }

            $servicesMap[$sid]['groups'][$gkey]['pieces'][] = $entry + [
                'x_line'    => $xLine,
                'y_line'    => $yLine,
                'file_id'   => $file?->id,
                'file_ext'  => $file?->extension,
                'file_kind' => $file?->kind,
                'file_url'  => $file ? route('files.raw', ['file' => $file->id]) : null,
            ];
        }

        // Flatten group maps into indexed arrays for JSON stability
        $services = array_values(array_map(function ($svc) {
            $svc['groups'] = array_values($svc['groups']);
            return $svc;
        }, $servicesMap));

        return response()->json([
            'services'         => $services,
            'missing_geometry' => $missingGeometry,
            'missing_material' => $missingMaterial,
            'missing_service'  => $missingService,
            'stats'            => [
                'lines_scanned'   => $lines->count(),
                'orders_included' => $lines->pluck('orders_id')->unique()->count(),
                'include_open'    => $includeOpen,
            ],
        ]);
    }

    /**
     * Find a document by code (quote or order).
     * Returns nesting services present in the document, with their nest_types
     * inferred from the paired MATERIAL tasks (triangulation).
     *
     * GET /nesting/document?code=OR-1712
     */
    public function document(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $code = $request->input('code');
        $doc  = Quotes::where('code', $code)->first();
        $type = 'quote';

        if (!$doc) {
            $doc  = Orders::where('code', $code)->first();
            $type = 'order';
        }

        if (!$doc) {
            return response()->json(['error' => 'Document non trouvé'], 404);
        }

        $linesField   = $type === 'quote' ? 'quote_lines_id' : 'order_lines_id';
        $lineIds      = $type === 'quote'
            ? $doc->QuoteLines()->pluck('id')
            : $doc->OrderLines()->pluck('id');

        // Services with is_nesting = true that have tasks in this document
        $serviceIds = Task::whereIn($linesField, $lineIds)
            ->join('methods_services', 'tasks.methods_services_id', '=', 'methods_services.id')
            ->where('methods_services.is_nesting', true)
            ->pluck('tasks.methods_services_id')
            ->unique()
            ->values();

        $services = MethodsServices::whereIn('id', $serviceIds)
            ->get()
            ->map(function ($service) use ($lineIds, $linesField) {
                // Lines where this service has tasks
                $serviceLineIds = Task::whereIn($linesField, $lineIds)
                    ->where('methods_services_id', $service->id)
                    ->pluck($linesField)
                    ->unique();

                // Nest types from paired MATERIAL tasks (triangulation) — join au lieu de whereHas
                $nestTypes = Task::whereIn("tasks.{$linesField}", $serviceLineIds)
                    ->where('tasks.methods_services_id', '!=', $service->id)
                    ->where('tasks.component_id', '>', 0)
                    ->join('products', 'products.id', '=', 'tasks.component_id')
                    ->join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
                    ->whereNotNull('methods_families.nest_type')
                    ->select('tasks.*')
                    ->with('Component.family')
                    ->get()
                    ->pluck('Component.family.nest_type')
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'id'         => $service->id,
                    'label'      => $service->label,
                    'color'      => $service->color,
                    'nest_types' => $nestTypes,
                ];
            })
            ->filter(fn($s) => $s['nest_types']->isNotEmpty())
            ->values();

        return response()->json([
            'document' => [
                'id'    => $doc->id,
                'type'  => $type,
                'code'  => $doc->code,
                'label' => $doc->label,
            ],
            'services' => $services,
        ]);
    }

    /**
     * Return parts to nest for a given document + service.
     * Each group carries its own nest_type (sheet/bar) from the paired material task.
     *
     * GET /nesting/parts?type=quote&id=42&service_id=5
     */
    public function parts(Request $request)
    {
        $request->validate([
            'type'       => 'required|in:quote,order',
            'id'         => 'required|integer',
            'service_id' => 'required|integer',
        ]);

        $type         = $request->input('type');
        $docId        = (int) $request->input('id');
        $serviceId    = (int) $request->input('service_id');
        $linesField   = $type === 'quote' ? 'quote_lines_id' : 'order_lines_id';
        $lineRelation = $type === 'quote' ? 'QuoteLines'      : 'OrderLines';
        $parentField  = $type === 'quote' ? 'quotes_id'       : 'orders_id';
        $detailsClass = $type === 'quote'
            ? \App\Models\Workflow\QuoteLineDetails::class
            : \App\Models\Workflow\OrderLineDetails::class;

        // Nesting-service tasks — no x_size filter (dimensions come from LineDetails)
        $nestingTasks = Task::where('methods_services_id', $serviceId)
            ->whereHas($lineRelation, fn($q) => $q->where($parentField, $docId))
            ->with($lineRelation)
            ->get();

        $lineIds = $nestingTasks->pluck($linesField)->unique();

        // LineDetails for piece dimensions (keyed by line id)
        $lineDetails = $detailsClass::whereIn($linesField, $lineIds)
            ->get()
            ->keyBy($linesField);

        // Paired MATERIAL tasks on the same lines (component_id > 0 with nesting family)
        // Join instead of whereHas to avoid nested subqueries (indexes on component_id + methods_families_id)
        $materialByLine = Task::whereIn("tasks.{$linesField}", $lineIds)
            ->where('tasks.methods_services_id', '!=', $serviceId)
            ->where('tasks.component_id', '>', 0)
            ->join('products', 'products.id', '=', 'tasks.component_id')
            ->join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
            ->whereNotNull('methods_families.nest_type')
            ->select('tasks.*')
            ->with('Component.family')
            ->get()
            ->groupBy($linesField);

        // Formats de stock disponibles par nest_type — join au lieu de whereHas
        $availableSheets = \App\Models\Products\Products::join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
            ->where('methods_families.nest_type', 'sheet')
            ->where('products.x_size', '>', 0)
            ->get(['products.id', 'products.label', 'products.x_size', 'products.y_size', 'products.thickness', 'products.material'])
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->label, 'x' => (float)$p->x_size, 'y' => (float)$p->y_size, 'thickness' => (float)$p->thickness, 'material' => trim($p->material ?? '')]);

        $availableBars = \App\Models\Products\Products::join('methods_families', 'methods_families.id', '=', 'products.methods_families_id')
            ->where('methods_families.nest_type', 'bar')
            ->where('products.x_size', '>', 0)
            ->get(['products.id', 'products.label', 'products.x_size', 'products.y_size', 'products.z_size', 'products.thickness', 'products.material'])
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->label, 'x' => (float)$p->x_size, 'y' => (float)$p->y_size, 'z' => (float)$p->z_size, 'thickness' => (float)$p->thickness, 'material' => trim($p->material ?? '')]);

        $groups = [];

        foreach ($nestingTasks as $task) {
            $lineId   = $task->$linesField;
            $matTask  = $materialByLine[$lineId]?->first();
            $nestType = $matTask?->Component?->family?->nest_type;

            if (!$nestType) continue;

            $details  = $lineDetails[$lineId] ?? null;
            $xTask    = (float) ($task->x_size ?? 0);
            $yTask    = (float) ($task->y_size ?? 0);
            $xDetail  = (float) ($details?->x_size ?? 0);
            $yDetail  = (float) ($details?->y_size ?? 0);

            // Dimensions effectives : task en priorité, detail en fallback
            $x = $xTask ?: $xDetail;
            $y = $yTask ?: $yDetail;
            if (!$x) continue;

            // Nom de la pièce + quantité depuis la ligne de commande/devis
            $lineModel = $task->$lineRelation;
            $pieceName = $lineModel?->label ?? $task->label;
            $qty       = (int) ($lineModel?->qty ?? $task->qty ?? 1);

            // Matière : champ material du composant (pas son label), fallback détail ligne
            $material = $task->material
                ?: ($matTask?->Component?->material
                ?: ($details?->material
                ?: ($matTask?->Component?->label ?? 'Non défini')));

            // Épaisseur de groupement : détail de ligne en priorité (épaisseur réelle de la pièce)
            $thickness = (float) ($details?->thickness ?: ($task->thickness ?: ($matTask?->Component?->thickness ?? 0)));

            // Warning épaisseur : composant vs détail ligne
            $compThickness    = (float) ($matTask?->Component?->thickness ?? 0);
            $detailThickness  = (float) ($details?->thickness ?? 0);
            $thicknessWarning = $compThickness > 0 && $detailThickness > 0
                && $compThickness !== $detailThickness;

            // Warning matière : composant vs détail ligne
            $compMaterial    = trim($matTask?->Component?->material ?? '');
            $detailMaterial  = trim($details?->material ?? '');
            $materialWarning = $compMaterial !== '' && $detailMaterial !== ''
                && $compMaterial !== $detailMaterial;

            if ($nestType === 'sheet') {
                $key = 'sheet|' . $material . '|' . $thickness;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'nest_type'        => 'sheet',
                        'material'         => $material,
                        'thickness'        => $thickness,
                        'available_sheets' => $availableSheets->values(),
                        'pieces'           => [],
                    ];
                }
            } else {
                $z   = (float) ($task->z_size ?? $details?->z_size ?? 0);
                $key = 'bar|' . $material . '|' . $y . '|' . $z;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'nest_type'        => 'bar',
                        'material'         => $material,
                        'y_size'           => $y,
                        'z_size'           => $z,
                        'available_sheets' => $availableBars->values(),
                        'pieces'           => [],
                    ];
                }
            }

            $groups[$key]['pieces'][] = [
                'task_id'          => $task->id,
                'label'            => $pieceName,
                'x'                => $x,
                'y'                => $y,
                'qty'              => $qty,
                'thickness_warn'   => $thicknessWarning,
                'thickness_comp'   => $compThickness ?: null,
                'thickness_detail' => $detailThickness ?: null,
                'material_warn'    => $materialWarning,
                'material_comp'    => $compMaterial ?: null,
                'material_detail'  => $detailMaterial ?: null,
            ];
        }

        return response()->json(['groups' => array_values($groups)]);
    }
}
