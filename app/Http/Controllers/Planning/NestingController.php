<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Http\Request;

class NestingController extends Controller
{
    public function index()
    {
        return view('planning.nesting-index');
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
