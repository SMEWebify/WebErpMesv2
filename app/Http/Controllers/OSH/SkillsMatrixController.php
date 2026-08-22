<?php

namespace App\Http\Controllers\OSH;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanResources\StoreTrainingTypeRequest;
use App\Http\Requests\HumanResources\UpdateTrainingTypeRequest;
use App\Models\HumanResources\TrainingType;
use App\Models\Methods\MethodsRessources;
use App\Models\User;
use App\Services\HumanResources\HabilitationService;
use Illuminate\Http\Request;

/**
 * Versatility matrix: who holds which authorisation, and on which machines.
 *
 * Read-only by design. The alerts listed here never prevent a task from being
 * assigned or started — they are there so the workshop knows.
 */
class SkillsMatrixController extends Controller
{
    public function __construct(private readonly HabilitationService $habilitations)
    {
    }

    public function index(Request $request)
    {
        $types = TrainingType::active()
            ->with('resources:id,code,label')
            ->orderBy('ordre')
            ->orderBy('label')
            ->get();

        $users = User::query()->orderBy('name')->get(['id', 'name', 'job_title']);

        return view('osh/osh-skills-matrix', [
            'TrainingTypes' => $types,
            'AllTrainingTypes' => TrainingType::orderBy('ordre')->orderBy('label')->get(),
            'Users' => $users,
            'Matrix' => $this->habilitations->matrix($users, $types),
            'Alerts' => $this->habilitations->taskAlerts(),
            'Resources' => MethodsRessources::orderBy('label')->get(['id', 'code', 'label']),
            'WarningDays' => $this->habilitations->warningDays(),
        ]);
    }

    public function storeType(StoreTrainingTypeRequest $request)
    {
        $validated = $request->validated();
        $validated['active'] = $request->has('active') ? $request->boolean('active') : true;

        $type = TrainingType::create($validated);
        $type->resources()->sync($request->input('resources', []));

        return redirect()
            ->route('osh.skills.matrix')
            ->with('success', __('general_content.training_type_created_success_trans_key'));
    }

    public function updateType(UpdateTrainingTypeRequest $request, int $id)
    {
        $type = TrainingType::findOrFail($id);

        $validated = $request->validated();
        $validated['active'] = $request->boolean('active');

        $type->update($validated);
        $type->resources()->sync($request->input('resources', []));

        return redirect()
            ->route('osh.skills.matrix')
            ->with('success', __('general_content.training_type_updated_success_trans_key'));
    }
}
