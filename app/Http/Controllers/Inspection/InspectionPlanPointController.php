<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Controller;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionPlan;
use App\Services\Inspection\CharacteristicReader;
use App\Services\Inspection\InspectionPlanPresenter;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Cotes bullées d'un plan de contrôle. Toute écriture exige un plan en
 * brouillon : un plan validé est figé.
 */
class InspectionPlanPointController extends Controller
{
    public const TYPES = ['dimension', 'diameter', 'radius', 'angle', 'gdt', 'thread', 'roughness', 'note'];

    public const GDT_SYMBOLS = [
        'straightness', 'flatness', 'circularity', 'cylindricity',
        'profile_line', 'profile_surface',
        'perpendicularity', 'parallelism', 'angularity',
        'position', 'concentricity', 'symmetry',
        'circular_runout', 'total_runout',
    ];

    public function store(Request $request, InspectionPlan $plan): JsonResponse
    {
        InspectionPlanController::ensureEditable($plan);

        $data = $this->validated($request);

        $point = DB::transaction(function () use ($plan, $data) {
            $number = (int) $plan->points()->lockForUpdate()->max('number') + 1;

            return $plan->points()->create($data + [
                'number' => $number,
                'order'  => $number,
                'label'  => $data['label'] ?? (string) $number,
            ]);
        });

        return response()->json(['point' => InspectionPlanPresenter::point($point->fresh())], 201);
    }

    public function update(Request $request, InspectionControlPoint $point): JsonResponse
    {
        $plan = $this->planOf($point);
        InspectionPlanController::ensureEditable($plan);

        $point->update($this->validated($request, partial: true));

        return response()->json(['point' => InspectionPlanPresenter::point($point->fresh())]);
    }

    public function destroy(InspectionControlPoint $point): JsonResponse
    {
        $plan = $this->planOf($point);
        InspectionPlanController::ensureEditable($plan);

        $point->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Renumérote dans l'ordre de lecture du plan tel qu'il est affiché (rotation
     * comprise) : page, puis de haut en bas par bandes (deux cotes à moins de 3 %
     * de hauteur l'une de l'autre sont sur la même ligne), puis de gauche à droite.
     */
    public function renumber(InspectionPlan $plan): JsonResponse
    {
        InspectionPlanController::ensureEditable($plan);

        // Coin haut-gauche du rectangle à l'écran : selon la rotation, ce n'est
        // pas le même coin que dans le repère de la page.
        $corner = function ($p) use ($plan) {
            $x = $p->box_x ?? 0;
            $y = $p->box_y ?? 0;
            $corners = [
                $plan->toView($x, $y),
                $plan->toView($x + ($p->box_w ?? 0), $y + ($p->box_h ?? 0)),
            ];

            return [min($corners[0][0], $corners[1][0]), min($corners[0][1], $corners[1][1])];
        };

        $points = $plan->points()->get()->sortBy([
            fn ($a, $b) => ($a->plan_page ?? 1) <=> ($b->plan_page ?? 1),
            fn ($a, $b) => (int) floor($corner($a)[1] / 0.03) <=> (int) floor($corner($b)[1] / 0.03),
            fn ($a, $b) => $corner($a)[0] <=> $corner($b)[0],
        ])->values();

        DB::transaction(function () use ($points) {
            foreach ($points as $index => $point) {
                $number = $index + 1;
                $point->update([
                    'number' => $number,
                    'order'  => $number,
                    'label'  => ctype_digit((string) $point->label) ? (string) $number : $point->label,
                ]);
            }
        });

        return response()->json([
            'points' => $plan->points()->get()->map(fn ($p) => InspectionPlanPresenter::point($p))->values(),
        ]);
    }

    /**
     * Lit la zone tracée (image découpée par le navigateur) avec l'IA et renvoie
     * les champs proposés. N'écrit rien : le bulleur pré-remplit la fiche.
     */
    public function readZone(Request $request, InspectionPlan $plan, CharacteristicReader $reader): JsonResponse
    {
        InspectionPlanController::ensureEditable($plan);

        $request->validate(['image' => ['required', 'file', 'max:2048']]);

        $bytes = (string) file_get_contents($request->file('image')->getRealPath());
        $info = @getimagesizefromstring($bytes);
        abort_if($info === false || $info[2] !== IMAGETYPE_PNG, 422, __('inspection.ai_failed'));

        try {
            return response()->json(['characteristic' => $reader->read($bytes)]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function planOf(InspectionControlPoint $point): InspectionPlan
    {
        abort_if($point->inspection_plan_id === null, 404);

        return $point->Plan;
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'nullable';
        $ratio = [$required, 'nullable', 'numeric', 'between:0,1'];

        $data = $request->validate([
            'label'               => [$required, 'nullable', 'string', 'max:255'],
            'characteristic_type' => [$required, Rule::in(self::TYPES)],
            'gdt_symbol'          => [$required, 'nullable', Rule::in(self::GDT_SYMBOLS)],
            'diameter_zone'       => [$required, 'boolean'],
            'material_modifier'   => [$required, 'nullable', Rule::in(['M', 'L'])],
            'datums'              => [$required, 'nullable', 'string', 'max:30', 'regex:/^[A-Z](\s*,\s*[A-Z]){0,4}\s*,?$/'],
            'count'               => [$required, 'integer', 'min:1', 'max:999'],
            'nominal_value'       => [$required, 'nullable', 'numeric'],
            'tol_min'             => [$required, 'nullable', 'numeric'],
            'tol_max'             => [$required, 'nullable', 'numeric'],
            'unit'                => [$required, 'nullable', 'string', 'max:10'],
            'frequency_type'      => [$required, Rule::in(['all', 'first', 'every_n', 'sample'])],
            'frequency_value'     => [$required, 'nullable', 'integer', 'min:1'],
            'plan_page'           => [$required, 'nullable', 'integer', 'min:1'],
            'grid_zone'           => [$required, 'nullable', 'string', 'max:10'],
            'methods_services_id' => [$required, 'nullable', 'integer', 'exists:methods_services,id'],
            'instrument_type'     => [$required, 'nullable', 'string', 'max:255'],
            'is_critical'         => [$required, 'boolean'],
            'is_fai'              => [$required, 'boolean'],
            'box_x'               => $ratio,
            'box_y'               => $ratio,
            'box_w'               => $ratio,
            'box_h'               => $ratio,
            'bubble_x'            => $ratio,
            'bubble_y'            => $ratio,
        ]);

        if (isset($data['datums'])) {
            // « A, » se tape en chemin vers « A,B » : la virgule finale est ignorée.
            $data['datums'] = rtrim(preg_replace('/\s+/', '', $data['datums']), ',');
        }

        // Une tolérance de forme / position n'a pas de symbole sans type « gdt ».
        if (($data['characteristic_type'] ?? null) !== null && $data['characteristic_type'] !== 'gdt') {
            $data['gdt_symbol'] = null;
            $data['material_modifier'] = null;
            $data['datums'] = null;
        }

        return $data;
    }
}
