<?php

namespace App\Http\Controllers\Methods;

use App\Http\Requests\Methods\StoreLossReasonRequest;
use App\Http\Requests\Methods\UpdateLossReasonRequest;
use App\Models\Workflow\LossReason;

/**
 * Référentiel des motifs de perte (devis et opportunités). Pas de suppression :
 * un motif déjà porté par des documents se désactive.
 */
class LossReasonsController extends Controller
{
    public function index()
    {
        return view('methods/methods-loss-reasons', [
            'reasons' => LossReason::ordered()
                ->withCount(['quotes', 'opportunities'])
                ->get(),
        ]);
    }

    public function store(StoreLossReasonRequest $request)
    {
        LossReason::create([
            'code'   => $request->validated('code'),
            'label'  => $request->validated('label'),
            'ordre'  => (int) ($request->validated('ordre') ?? 0),
            'active' => true,
        ]);

        return redirect()->route('methods.loss-reason')
            ->with('success', __('losses.created'));
    }

    public function update(UpdateLossReasonRequest $request, int $id)
    {
        LossReason::findOrFail($id)->update([
            'label'  => $request->validated('label'),
            'ordre'  => (int) ($request->validated('ordre') ?? 0),
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('methods.loss-reason')
            ->with('success', __('losses.updated'));
    }
}
