<?php

namespace App\Http\Controllers\Methods;

use App\Http\Requests\Methods\StoreAffairCategoryRequest;
use App\Http\Requests\Methods\UpdateAffairCategoryRequest;
use App\Models\Methods\MethodsServices;
use App\Models\Workflow\AffairCategory;

/**
 * Référentiel des catégories d'affaire et de leurs opérations obligatoires.
 * Pas de suppression : une catégorie déjà portée par des affaires se désactive.
 */
class AffairCategoriesController extends Controller
{
    public function index()
    {
        return view('methods/methods-affair-categories', [
            'categories' => AffairCategory::ordered()
                ->with('requiredServices:id,code,label')
                ->withCount(['opportunities', 'quotes', 'orders'])
                ->get(),
            'services' => MethodsServices::orderBy('ordre')->orderBy('code')->get(['id', 'code', 'label']),
        ]);
    }

    public function store(StoreAffairCategoryRequest $request)
    {
        $category = AffairCategory::create([
            'code'   => $request->validated('code'),
            'label'  => $request->validated('label'),
            'ordre'  => (int) ($request->validated('ordre') ?? 0),
            'active' => true,
            'requires_manufacturing_dossier' => $request->boolean('requires_manufacturing_dossier'),
            'requires_risk_register' => $request->boolean('requires_risk_register'),
        ]);
        $category->requiredServices()->sync($request->validated('required_services') ?? []);

        return redirect()->route('methods.affair-category')
            ->with('success', __('affairs.category.created'));
    }

    public function update(UpdateAffairCategoryRequest $request, int $id)
    {
        $category = AffairCategory::findOrFail($id);
        $category->update([
            'label'  => $request->validated('label'),
            'ordre'  => (int) ($request->validated('ordre') ?? 0),
            'active' => $request->boolean('active'),
            // Vaut pour les lignes créées ensuite : les lignes existantes gardent leur exigence.
            'requires_manufacturing_dossier' => $request->boolean('requires_manufacturing_dossier'),
            // Avertissement à la revue de contrat seulement, jamais bloquant.
            'requires_risk_register' => $request->boolean('requires_risk_register'),
        ]);
        // Le formulaire de modification envoie toujours la liste : vide = plus aucune exigence.
        $category->requiredServices()->sync($request->validated('required_services') ?? []);

        return redirect()->route('methods.affair-category')
            ->with('success', __('affairs.category.updated'));
    }
}
