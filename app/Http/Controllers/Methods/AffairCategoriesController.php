<?php

namespace App\Http\Controllers\Methods;

use App\Http\Requests\Methods\StoreAffairCategoryRequest;
use App\Http\Requests\Methods\UpdateAffairCategoryRequest;
use App\Models\Workflow\AffairCategory;

/**
 * Référentiel des catégories d'affaire. Pas de suppression : une catégorie
 * déjà portée par des affaires se désactive.
 */
class AffairCategoriesController extends Controller
{
    public function index()
    {
        return view('methods/methods-affair-categories', [
            'categories' => AffairCategory::ordered()
                ->withCount(['opportunities', 'quotes', 'orders'])
                ->get(),
        ]);
    }

    public function store(StoreAffairCategoryRequest $request)
    {
        AffairCategory::create([
            'code'   => $request->validated('code'),
            'label'  => $request->validated('label'),
            'ordre'  => (int) ($request->validated('ordre') ?? 0),
            'active' => true,
        ]);

        return redirect()->route('methods.affair-category')
            ->with('success', __('affairs.category.created'));
    }

    public function update(UpdateAffairCategoryRequest $request, int $id)
    {
        AffairCategory::findOrFail($id)->update([
            'label'  => $request->validated('label'),
            'ordre'  => (int) ($request->validated('ordre') ?? 0),
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('methods.affair-category')
            ->with('success', __('affairs.category.updated'));
    }
}
