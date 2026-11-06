<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Products\Nest;
use App\Models\Products\StockLocationProducts;
use App\Services\Stock\NestRecorder;
use App\Services\Stock\SheetRemnants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Imbrications réalisées : liste, saisie à la main (sortie d'une tôle répartie sur
 * plusieurs lignes de commande) et fiche (pièces, mouvements, coût par ligne).
 */
class NestsController extends Controller
{
    public function __construct(
        private readonly NestRecorder $recorder,
        private readonly SheetRemnants $remnants,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $nests = Nest::with('product:id,code,label')
            ->withCount('parts')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('external_id', $search)))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('products.nests-index', ['nests' => $nests, 'search' => $search]);
    }

    public function create(Request $request)
    {
        $slp = StockLocationProducts::with('Product', 'StockLocation')->findOrFail((int) $request->query('stock_line'));

        return view('products.nests-create', [
            'StockLocationProduct' => $slp,
            'Product'              => $slp->Product,
            'Pieces'               => $this->remnants->pieces($slp),
            'Tasks'                => $this->recorder->candidateTasks($slp->Product),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_location_products_id' => 'required|integer|exists:stock_location_products,id',
            'piece'                      => 'required|string|max:255',
            'sheet_qty'                  => 'required|numeric|min:0.001',
            'name'                       => 'required|string|max:255',
            'usage_percent'              => 'nullable|numeric|min:0|max:100',
            'comment'                    => 'nullable|string|max:2000',
            'parts'                      => 'nullable|array',
            'parts.*'                    => 'nullable|numeric|min:0',
            'remnants'                   => 'nullable|array',
        ]);

        $slp = StockLocationProducts::with('Product')->findOrFail($validated['stock_location_products_id']);

        $parts = [];
        foreach ($validated['parts'] ?? [] as $taskId => $qty) {
            if ($qty !== null && $qty !== '' && (float) $qty > 0) {
                $parts[] = ['task_id' => (int) $taskId, 'qty' => (float) $qty];
            }
        }

        try {
            $nest = $this->recorder->recordManual(
                $slp,
                $validated['piece'],
                (float) $validated['sheet_qty'],
                $parts,
                $this->remnants->normalize($request->input('remnants')),
                [
                    'name'          => $validated['name'],
                    'usage_percent' => $validated['usage_percent'] ?? null,
                    'comment'       => $validated['comment'] ?? null,
                ],
                Auth::user(),
            );
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('products.nests.show', ['nest' => $nest->id])
            ->with('success', __('nests.created'));
    }

    public function show(Nest $nest)
    {
        $nest->load([
            'product:id,code,label',
            'user:id,name',
            'parts.task',
            'parts.orderLine.order',
            'stockMoves' => fn ($q) => $q->with(['StockLocationProducts.StockLocation', 'batch'])->orderBy('id'),
        ]);

        return view('products.nests-show', [
            'Nest'       => $nest,
            'Allocation' => $this->recorder->allocation($nest),
        ]);
    }
}
