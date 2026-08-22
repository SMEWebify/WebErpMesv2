<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreInventoryRequest;
use App\Models\Products\Inventory;
use App\Models\Products\StockLocation;
use App\Services\Files\FileStorageService;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\InventoryXlsxExporter;
use App\Services\Inventory\InventoryXlsxImporter;
use App\Services\SelectDataService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly InventoryXlsxExporter $exporter,
        private readonly InventoryXlsxImporter $importer,
        private readonly FileStorageService $fileStorage,
        private readonly SelectDataService $selectDataService,
    ) {
    }

    public function index()
    {
        return view('products.inventories.index', [
            'stockLocations' => StockLocation::orderBy('code')->get(['id', 'code', 'label']),
            'categories'     => $this->selectDataService->getFamilies(),
        ]);
    }

    public function indexJson(Request $request): JsonResponse
    {
        $inventories = Inventory::query()
            ->when($request->filled('status'), fn ($q) => $q->where('statu', (int) $request->input('status')))
            ->with(['creator:id,name', 'validator:id,name'])
            ->withCount('details')
            ->orderByDesc('created_at')
            ->get(['id', 'code', 'label', 'scope_type', 'statu', 'start_date', 'end_date', 'created_by', 'validated_by', 'created_at']);

        return response()->json(['inventories' => $inventories]);
    }

    public function store(StoreInventoryRequest $request): RedirectResponse
    {
        $inventory = $this->inventoryService->create(
            $request->validated(),
            Auth::id(),
        );

        return redirect()->route('products.inventories.show', ['id' => $inventory->id])
            ->with('success', __('general_content.inventory_created_trans_key'));
    }

    public function show(int $id)
    {
        $inventory = Inventory::with([
            'creator:id,name',
            'validator:id,name',
            'file',
        ])->findOrFail($id);

        return view('products.inventories.show', [
            'inventory' => $inventory,
        ]);
    }

    public function showJson(int $id): JsonResponse
    {
        $inventory = Inventory::with([
            'creator:id,name',
            'validator:id,name',
            'file',
        ])->findOrFail($id);

        $details = $inventory->details()
            ->with([
                'product:id,code,label',
                'stockLocationProduct:id,code,stock_locations_id',
                'stockLocationProduct.StockLocation:id,code',
                'batch:id,number',
            ])
            ->get();

        $summary = $this->summarise($details);

        return response()->json([
            'inventory' => $inventory,
            'details'   => $details,
            'summary'   => $summary,
        ]);
    }

    public function exportXlsx(int $id, int $blind = 0)
    {
        $inventory = Inventory::findOrFail($id);
        $path = $this->exporter->export($inventory, (bool) $blind);

        // First export moves the header to "exported" so the UI can guide the
        // user towards the upload step.
        if ($inventory->isDraft()) {
            $inventory->forceFill(['statu' => Inventory::STATUS_EXPORTED])->save();
        }

        return response()->download(
            $path,
            basename($path),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * Parse the uploaded counting file, validate the rows, and return the diff
     * without touching the DB. The React UI uses this to display errors and a
     * summary card before the user confirms the import.
     */
    public function previewImport(int $id, Request $request): JsonResponse
    {
        $inventory = Inventory::findOrFail($id);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $result = $this->importer->preview($inventory, $request->file('file'));

        return response()->json($result);
    }

    /**
     * Persist the counted quantities from the uploaded file and archive it
     * on the GED so it can be downloaded again after validation. On errors,
     * nothing is written and the errors are returned for display.
     */
    public function import(int $id, Request $request): JsonResponse
    {
        $inventory = Inventory::findOrFail($id);

        if ($inventory->isLocked()) {
            return response()->json([
                'errors' => [['row' => 0, 'message' => __('general_content.inventory_already_locked_trans_key')]],
            ], 422);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $uploaded = $request->file('file');
        $result = $this->importer->apply($inventory, $uploaded, Auth::id());

        if ($result['errors'] !== []) {
            return response()->json($result, 422);
        }

        // Archive the counting file on the GED. Detach any previous version so
        // re-imports don't leave orphaned files behind.
        $previous = $inventory->file;

        $file = $this->fileStorage->store($uploaded, [
            'comment' => 'Fichier de comptage inventaire ' . $inventory->code,
        ]);
        $this->fileStorage->attach($file, $inventory);

        $inventory->forceFill(['file_id' => $file->id])->save();

        if ($previous !== null) {
            $this->fileStorage->detach($previous, $inventory);
        }

        return response()->json($result);
    }

    public function validateInventory(int $id): JsonResponse
    {
        $inventory = Inventory::findOrFail($id);

        try {
            $this->inventoryService->validate($inventory, Auth::id());
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'   => true,
            'inventory' => $inventory->fresh(['entryMove', 'exitMove', 'file']),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $inventory = Inventory::findOrFail($id);

        try {
            $this->inventoryService->cancel($inventory);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'inventory' => $inventory->fresh()]);
    }

    /**
     * Rollup for the show screen: how many lines counted, variance totals,
     * cost impact. Kept in the controller so the JSON shape stays close to
     * what the React summary card renders.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Products\InventoryDetail>  $details
     * @return array<string, float|int>
     */
    private function summarise($details): array
    {
        $counted = $details->whereNotNull('counted_qty');

        $positive = 0;
        $negative = 0;
        $positiveValue = 0.0;
        $negativeValue = 0.0;

        foreach ($counted as $d) {
            $variance = (float) $d->counted_qty - (float) $d->theoretical_qty;
            if ($variance > 0) {
                $positive++;
                $positiveValue += $variance * (float) $d->unit_cost;
            } elseif ($variance < 0) {
                $negative++;
                $negativeValue += $variance * (float) $d->unit_cost;
            }
        }

        return [
            'total_lines'             => $details->count(),
            'counted_lines'           => $counted->count(),
            'positive_variance_count' => $positive,
            'negative_variance_count' => $negative,
            'positive_variance_value' => round($positiveValue, 2),
            'negative_variance_value' => round($negativeValue, 2),
            'net_variance_value'      => round($positiveValue + $negativeValue, 2),
        ];
    }
}
