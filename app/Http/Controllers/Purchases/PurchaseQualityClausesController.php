<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchases\PurchaseQualityClause;
use App\Models\Purchases\PurchaseQualityClauseRule;
use App\Services\Purchases\PurchaseCriticality;
use Illuminate\Http\Request;

/**
 * Référentiel des clauses qualité d'achat et de leurs règles d'application
 * (permission purchase-quality-clauses-manage, posée sur les routes).
 *
 * Pas de suppression de clause : une clause déjà copiée sur des commandes se
 * désactive. Les commandes émises gardent leur copie quoi qu'il arrive ici.
 */
class PurchaseQualityClausesController extends Controller
{
    public function index()
    {
        return view('purchases/purchases-quality-clauses', [
            'clauses' => PurchaseQualityClause::ordered()
                ->with('rules')
                ->withCount('orderCopies')
                ->get(),
            'options' => PurchaseCriticality::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'    => 'required|string|max:30|unique:purchase_quality_clauses,code',
            'label'   => 'required|string|max:255',
            'text_fr' => 'required|string',
            'text_en' => 'nullable|string',
            'ordre'   => 'nullable|integer|min:0',
        ]);

        PurchaseQualityClause::create([
            'ordre'  => (int) ($validated['ordre'] ?? 0),
            'active' => true,
        ] + $validated);

        return redirect()->route('purchases.quality-clauses')->with('success', __('purchase_quality.created'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'label'   => 'required|string|max:255',
            'text_fr' => 'required|string',
            'text_en' => 'nullable|string',
            'ordre'   => 'nullable|integer|min:0',
        ]);

        PurchaseQualityClause::findOrFail($id)->update([
            'ordre'  => (int) ($validated['ordre'] ?? 0),
            'active' => $request->boolean('active'),
        ] + $validated);

        return redirect()->route('purchases.quality-clauses')->with('success', __('purchase_quality.updated'));
    }

    public function storeRule(Request $request, int $id)
    {
        $clause    = PurchaseQualityClause::findOrFail($id);
        $validated = $request->validate(PurchaseCriticality::validationRules());

        if (count(array_filter($validated)) === 0) {
            return redirect()->route('purchases.quality-clauses')
                ->with('error', __('purchase_quality.rule_needs_criterion'));
        }

        $clause->rules()->create($validated);

        return redirect()->route('purchases.quality-clauses')->with('success', __('purchase_quality.rule_created'));
    }

    public function destroyRule(int $id, int $ruleId)
    {
        PurchaseQualityClauseRule::where('purchase_quality_clause_id', $id)->findOrFail($ruleId)->delete();

        return redirect()->route('purchases.quality-clauses')->with('success', __('purchase_quality.rule_deleted'));
    }
}
