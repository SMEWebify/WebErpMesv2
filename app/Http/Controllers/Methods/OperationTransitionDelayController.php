<?php

namespace App\Http\Controllers\Methods;

use App\Http\Requests\Methods\StoreOperationTransitionDelayRequest;
use App\Http\Requests\Methods\UpdateOperationTransitionDelayRequest;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\OperationTransitionDelay;

class OperationTransitionDelayController extends Controller
{
    public function index()
    {
        // Seuls les services productifs enchaînent des tâches — matière, fournitures
        // et sous-traitance ne consomment pas de capacité interne (cf. MethodsServices).
        $services = MethodsServices::where('type', MethodsServices::TYPE_PRODUCTIVE)
            ->orderBy('ordre')
            ->orderBy('code')
            ->get(['id', 'code', 'label']);

        $delays = OperationTransitionDelay::with(['fromService:id,code,label', 'toService:id,code,label'])
            ->orderBy('from_service_id')
            ->orderBy('to_service_id')
            ->get();

        return view('methods.methods-operation-transition-delays', [
            'services' => $services,
            'delays'   => $delays,
            'defaultHours' => (float) config('planning.inter_operation_hours', 0),
            'minGapHours'  => (float) config('planning.min_operation_gap_hours', 0),
        ]);
    }

    public function store(StoreOperationTransitionDelayRequest $request)
    {
        OperationTransitionDelay::create($request->only([
            'from_service_id',
            'to_service_id',
            'transfer_hours',
        ]));

        return redirect()
            ->route('methods.operation-transition-delay')
            ->with('success', 'Délai inter-opérations créé.');
    }

    public function update(UpdateOperationTransitionDelayRequest $request, int $id)
    {
        $delay = OperationTransitionDelay::findOrFail($id);
        $delay->update($request->only('transfer_hours'));

        return redirect()
            ->route('methods.operation-transition-delay')
            ->with('success', 'Délai inter-opérations mis à jour.');
    }

    public function destroy(int $id)
    {
        OperationTransitionDelay::findOrFail($id)->delete();

        return redirect()
            ->route('methods.operation-transition-delay')
            ->with('success', 'Délai inter-opérations supprimé.');
    }
}
