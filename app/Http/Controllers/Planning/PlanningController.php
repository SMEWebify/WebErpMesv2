<?php

namespace App\Http\Controllers\Planning;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Jobs\CalculateTaskDates;
use App\Jobs\CalculateTaskResources;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsServices;
use App\Models\Times\TimesBanckHoliday;
use App\Models\Methods\MethodsRessources;
use App\Services\ResourceCapacityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class PlanningController extends Controller
{
    /** Mailles d'affichage de la charge. */
    public const GRANULARITY_SERVICE = 'service';
    public const GRANULARITY_RESOURCE = 'resource';

    /** Ligne des tâches de la période qui n'ont encore aucune ressource. */
    private const ROW_UNASSIGNED = 'unassigned';

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->addMonths(1)->format('Y-m-d'));

        $displayHoursDiff = $request->input('display_hours_diff', false);
        $granularity = $this->granularity($request);

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return redirect()->route('production.load.planning')->withErrors(['The start date must be before or equal to the end date.']);
        }

        $taches = $this->getTasks($startDate, $endDate);

        if ($taches->isEmpty() && $this->countTaskNullRessource() < 1) {
            return redirect()->route('production.task')->with('error', 'No task in planning');
        }

        // Le payload initial est construit par le même code que l'endpoint JSON,
        // pour que le premier affichage et les rafraîchissements ne divergent pas.
        $initialData = $this->buildData($startDate, $endDate, $granularity, $taches);

        return view('workflow/planning-index', [
            'initialData'      => $initialData,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'displayHoursDiff' => $displayHoursDiff,
            'granularity'      => $granularity,
        ]);
    }

    // -------------------------------------------------------------------------
    // API endpoints for React
    // -------------------------------------------------------------------------

    public function dataJson(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->addMonths(1)->format('Y-m-d'));

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return response()->json(['error' => 'The start date must be before or equal to the end date.'], 422);
        }

        return response()->json($this->buildData($startDate, $endDate, $this->granularity($request)));
    }

    private function granularity(Request $request): string
    {
        return $request->input('granularity') === self::GRANULARITY_RESOURCE
            ? self::GRANULARITY_RESOURCE
            : self::GRANULARITY_SERVICE;
    }

    /**
     * Charge de la période, à la maille demandée.
     *
     * Les deux mailles partagent la même convention de date que l'écran a
     * toujours utilisée — les heures d'une tâche sont imputées à son end_date —
     * pour que les totaux d'une vue à l'autre restent comparables.
     */
    private function buildData(string $startDate, string $endDate, string $granularity, $taches = null): array
    {
        $taches = $taches ?? $this->getTasks($startDate, $endDate);
        $dates  = $this->generatePossibleDates($startDate, $endDate);

        if ($granularity === self::GRANULARITY_RESOURCE) {
            [$hours, $tasks] = $this->calculateHoursAndTasksPerResource($taches);
            $rows = $this->mapResources($dates, $hours);
        } else {
            [$hours, $tasks] = $this->calculateHoursAndTasks($taches);
            $rows = $this->mapServices($this->getServices());
        }

        return [
            'granularity'            => $granularity,
            'rows'                   => $rows,
            'hoursPerRowDay'         => $hours,
            'tasksPerRowDay'         => $tasks,
            'possibleDates'          => $dates,
            'bankHolidays'           => $this->getBankHolidays(),
            'countTaskNullDate'      => $this->countTaskNullDate(),
            'countTaskNullRessource' => $this->countTaskNullRessource(),
        ];
    }

    /**
     * POST — dispatch the date calculation job.
     */
    public function calculateDates()
    {
        Cache::forget(CalculateTaskDates::CACHE_KEY);
        CalculateTaskDates::dispatchAfterResponse();

        return response()->json(['dispatched' => true]);
    }

    /**
     * POST — dispatch the resource assignment job.
     */
    public function calculateResources(Request $request)
    {
        Cache::forget(CalculateTaskResources::CACHE_KEY);
        // `rebalance` reprend en plus les affectations posées automatiquement, pour
        // les redistribuer après un changement de capacité (nouvelle machine, régime
        // horaire, arrêt planifié). Les choix humains et les tâches déjà démarrées
        // ne sont pas touchés.
        CalculateTaskResources::dispatchAfterResponse($request->boolean('rebalance'));

        return response()->json(['dispatched' => true]);
    }

    /**
     * GET — return both job statuses for React polling.
     */
    public function calculationStatus()
    {
        $dateState     = Cache::get(CalculateTaskDates::CACHE_KEY, []);
        $resourceState = Cache::get(CalculateTaskResources::CACHE_KEY, []);

        return response()->json([
            'dates' => [
                'jobStatus'         => $dateState['status'] ?? null,
                'progress'          => $dateState['progress'] ?? 0,
                'count'             => $dateState['count'] ?? 0,
                'messages'          => $dateState['messages'] ?? [],
                'countTaskNullDate' => $this->countTaskNullDate(),
            ],
            'resources' => [
                'jobStatus'              => $resourceState['status'] ?? null,
                'progress'               => $resourceState['progress'] ?? 0,
                'count'                  => $resourceState['count'] ?? 0,
                'messages'               => $resourceState['messages'] ?? [],
                'countTaskNullRessource' => $this->countTaskNullRessource(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getTasks($startDate, $endDate)
    {
        return Task::with(['service', 'OrderLines', 'resources'])
                    ->whereBetween('end_date', [$startDate, $endDate])
                    ->whereNotNull('order_lines_id')
                    ->where(function (Builder $query) {
                        return $query->where('tasks.type', 1)
                                    ->orWhere('tasks.type', 7);
                    })->get();
    }

    /**
     * Charge par ressource et par jour.
     *
     * Une tâche pèse sur chacune de ses affectations : la totalité des heures sur
     * la machine, la quotité `load_factor` sur la main-d'œuvre. Les tâches encore
     * sans ressource sont regroupées sur une ligne à part — les faire disparaître
     * du tableau donnerait une charge d'atelier fausse.
     */
    private function calculateHoursAndTasksPerResource($taches): array
    {
        $hoursPerRowDay = [];
        $tasksPerRowDay = [];

        foreach ($taches as $tache) {
            $jour = (new Carbon($tache['end_date']))->format('Y-m-d');

            if ($tache->resources->isEmpty()) {
                $hoursPerRowDay[self::ROW_UNASSIGNED][$jour] = ($hoursPerRowDay[self::ROW_UNASSIGNED][$jour] ?? 0)
                    + $tache->TotalTime();
                $tasksPerRowDay[self::ROW_UNASSIGNED][$jour][] = $tache->id;
                continue;
            }

            foreach ($tache->resources as $resource) {
                $key    = (string) $resource->id;
                $factor = (float) ($resource->pivot->load_factor ?? 1);

                $hoursPerRowDay[$key][$jour] = ($hoursPerRowDay[$key][$jour] ?? 0)
                    + $tache->TotalTime() * $factor;
                $tasksPerRowDay[$key][$jour][] = $tache->id;
            }
        }

        return [$hoursPerRowDay, $tasksPerRowDay];
    }

    /**
     * Lignes ressources du tableau, avec la capacité réelle de chaque jour :
     * régime horaire, fériés, arrêts machine et absences validées. C'est plus
     * juste que la maille service, où la capacité d'une machine partagée doit
     * être répartie entre les services qu'elle réalise.
     */
    private function mapResources(array $dates, array $hoursPerRowDay): \Illuminate\Support\Collection
    {
        $capacityService = app(ResourceCapacityService::class);

        $rows = MethodsRessources::with(['shiftPattern.slots', 'section:id,label'])
            ->orderBy('ordre')
            ->get()
            ->map(function (MethodsRessources $resource) use ($dates, $capacityService) {
                $perDay = [];

                foreach ($dates as $date) {
                    $perDay[$date] = $capacityService->availableHours($resource, Carbon::parse($date));
                }

                return [
                    'id'             => (string) $resource->id,
                    'label'          => $resource->label,
                    'avatar'         => $resource->picture ? '/storage/images/ressources/' . $resource->picture : null,
                    'capacity'       => round($resource->dailyCapacity(), 2),
                    'capacityPerDay' => $perDay,
                    'isLabor'        => (bool) $resource->is_labor,
                    'section'        => $resource->section?->label,
                ];
            })
            ->values();

        if (isset($hoursPerRowDay[self::ROW_UNASSIGNED])) {
            $rows->push([
                'id'             => self::ROW_UNASSIGNED,
                'label'          => __('general_content.load_planning_unassigned_trans_key'),
                'avatar'         => null,
                'capacity'       => 0,
                'capacityPerDay' => [],
                'isLabor'        => false,
                'section'        => null,
            ]);
        }

        return $rows;
    }

    private function getServices()
    {
        // withCount('services') sur les ressources : une machine partagée entre
        // plusieurs services ne doit pas voir sa capacité comptée en entier dans
        // chacun d'eux (cf. mapServices).
        return MethodsServices::with(['Ressources' => fn ($query) => $query->withCount('services')])
                    ->where(function (Builder $query) {
                        return $query->where('type', MethodsServices::TYPE_PRODUCTIVE)
                                    ->orWhere('type', MethodsServices::TYPE_SUB_CONTRACTING);
                    })->get();
    }

    /**
     * Map services for the frontend.
     * capacity = sum of resource daily capacities — 0 if no resources configured.
     * React uses this to decide whether to show a custom-capacity input.
     *
     * Une ressource pouvant réaliser plusieurs services, sa capacité est répartie
     * à parts égales entre eux : la somme des capacités par service reste ainsi
     * égale à la capacité réelle de l'atelier, sans double comptage.
     */
    private function mapServices($services): \Illuminate\Support\Collection
    {
        return $services->map(fn ($s) => [
            'id'       => (string) $s->id,
            'label'    => $s->label,
            'avatar'   => $s->picture ? '/storage/images/methods/' . $s->picture : null,
            // capacity is stored weekly — convert to daily (÷5) for load rate display
            'capacity' => round(
                $s->Ressources->sum(fn ($resource) => $resource->capacity / max(1, (int) $resource->services_count))
                    / \App\Models\Methods\MethodsRessources::WORKING_DAYS_PER_WEEK,
                2
            ),
        ])->values();
    }

    private function countTaskNullRessource()
    {
        // Seules les tâches productives doivent apparaître comme « sans ressource » :
        // matière, fournitures et sous-traitance n'en consomment aucune.
        return Task::productive()
                    ->whereNotNull('order_lines_id')
                    ->whereDoesntHave('resources')
                    ->count();
    }

    private function countTaskNullDate()
    {
        return Task::whereNull('end_date')
                    ->whereNotNull('order_lines_id')
                    ->where(function (Builder $query) {
                        return $query->where('tasks.type', 1)
                                    ->orWhere('tasks.type', 7);
                    })->count();
    }

    /**
     * Returns raw hours worked per service per day (not percentages).
     * React computes load rates using the service's effective capacity.
     */
    private function calculateHoursAndTasks($taches): array
    {
        $hoursPerServiceDay = [];
        $tasksPerServiceDay = [];

        foreach ($taches as $tache) {
            $serviceId = (string) $tache['methods_services_id'];
            $jour      = (new Carbon($tache['end_date']))->format('Y-m-d');

            $hoursPerServiceDay[$serviceId][$jour] = ($hoursPerServiceDay[$serviceId][$jour] ?? 0)
                + $tache->TotalTime();

            $tasksPerServiceDay[$serviceId][$jour][] = $tache->id;
        }

        return [$hoursPerServiceDay, $tasksPerServiceDay];
    }

    private function generatePossibleDates($startDate, $endDate): array
    {
        $dates       = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $dates[]     = $currentDate;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return $dates;
    }

    private function getBankHolidays(): array
    {
        return TimesBanckHoliday::all()->mapWithKeys(function ($holiday) {
            if ($holiday->fixed) {
                return [Carbon::parse($holiday->date)->format('m-d') => $holiday->label];
            }

            return [Carbon::parse($holiday->date)->toDateString() => $holiday->label];
        })->toArray();
    }
}
