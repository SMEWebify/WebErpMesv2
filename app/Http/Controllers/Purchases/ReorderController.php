<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsTools;
use App\Services\SelectDataService;
use App\Services\Stock\ReorderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Écran de réapprovisionnement : propositions d'achat (seuil mini + besoins réservés −
 * stock − en commande) et création de commandes d'achat brouillon par fournisseur.
 * ?scope=tools restreint aux articles de stock des outils (Méthodes › Outils).
 * ?products[]=… restreint à des articles précis (bouton « Commander » d'une ligne).
 */
class ReorderController extends Controller
{
    public function __construct(private ReorderService $reorderService, private SelectDataService $selectDataService)
    {
    }

    public function index(Request $request)
    {
        return view('purchases/purchases-reorder', [
            'scope'    => $request->query('scope') === 'tools' ? 'tools' : 'all',
            'products' => array_values(array_map('intval', (array) $request->query('products', []))),
        ]);
    }

    public function json(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope'      => 'nullable|in:all,tools',
            'products'   => 'nullable|array',
            'products.*' => 'integer',
        ]);

        $ids = !empty($validated['products']) ? $validated['products'] : null;
        if (($validated['scope'] ?? 'all') === 'tools' && $ids === null) {
            $ids = MethodsTools::whereNotNull('products_id')->pluck('products_id')->unique()->values()->all();
        }
        // une liste explicite (« Commander » sur un outil) propose l'article même sans manque
        $explicit = !empty($validated['products']);

        return response()->json([
            'rows'      => $this->reorderService->suggestions($ids, !$explicit),
            'suppliers' => $this->selectDataService->getSupplier()
                ->map(fn ($c) => ['id' => $c->id, 'label' => trim($c->code . ' - ' . $c->label)])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lines'                      => 'required|array|min:1',
            'lines.*.product_id'         => 'required|integer|exists:products,id',
            'lines.*.companies_id'       => 'required|integer|exists:companies,id',
            'lines.*.qty'                => 'required|numeric|gt:0',
            'lines.*.price'              => 'required|numeric|min:0',
            'lines.*.stock_locations_id' => 'nullable|integer|exists:stock_locations,id',
        ], [
            'lines.*.companies_id.required' => 'Choisissez un fournisseur pour chaque ligne cochée.',
        ]);

        try {
            $purchases = $this->reorderService->createDraftPurchases($validated['lines']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'purchases' => $purchases->map(fn ($p) => [
                'id'    => $p->id,
                'code'  => $p->code,
                'lines' => $p->PurchaseLines()->count(),
                'url'   => route('purchases.show', ['id' => $p->id]),
            ])->values(),
        ], 201);
    }
}
