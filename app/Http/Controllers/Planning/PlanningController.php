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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->addMonths(1)->format('Y-m-d'));

        $displayHoursDiff = $request->input('display_hours_diff', false);

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return redirect()->route('production.load.planning')->withErrors(['The start date must be before or equal to the end date.']);
        }

        $taches   = $this->getTasks($startDate, $endDate);
        $services = $this->getServices();

        if ($taches->isEmpty() && $this->countTaskNullRessource() < 1) {
            return redirect()->route('production.task')->with('error', 'No task in planning');
        }

        [$hoursPerServiceDay, $tasksPerServiceDay] = $this->calculateHoursAndTasks($taches);

        $possibleDates          = $this->generatePossibleDates($startDate, $endDate);
        $countTaskNullDate      = $this->countTaskNullDate();
        $countTaskNullRessource = $this->countTaskNullRessource();
        $bankHolidays           = $this->getBankHolidays();

        return view('workflow/planning-index', compact(
            'taches',
            'countTaskNullRessource',
            'countTaskNullDate',
            'tasksPerServiceDay',
            'hoursPerServiceDay',
            'services',
            'possibleDates',
            'startDate',
            'endDate',
            'displayHoursDiff',
            'bankHolidays',
        ));
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

        $taches   = $this->getTasks($startDate, $endDate);
        $services = $this->getServices();

        [$hoursPerServiceDay, $tasksPerServiceDay] = $this->calculateHoursAndTasks($taches);
        $possibleDates = $this->generatePossibleDates($startDate, $endDate);

        return response()->json([
            'services'               => $this->mapServices($services),
            'possibleDates'          => $possibleDates,
            'hoursPerServiceDay'     => $hoursPerServiceDay,
            'tasksPerServiceDay'     => $tasksPerServiceDay,
            'bankHolidays'           => $this->getBankHolidays(),
            'countTaskNullDate'      => $this->countTaskNullDate(),
            'countTaskNullRessource' => $this->countTaskNullRessource(),
        ]);
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
    public function calculateResources()
    {
        Cache::forget(CalculateTaskResources::CACHE_KEY);
        CalculateTaskResources::dispatchAfterResponse();

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
        return Task::with('service')
                    ->whereBetween('end_date', [$startDate, $endDate])
                    ->whereNotNull('order_lines_id')
                    ->where(function (Builder $query) {
                        return $query->where('tasks.type', 1)
                                    ->orWhere('tasks.type', 7);
                    })->get();
    }

    private function getServices()
    {
        return MethodsServices::with('Ressources')
                    ->where(function (Builder $query) {
                        return $query->where('type', 1)
                                    ->orWhere('type', 7);
                    })->get();
    }

    /**
     * Map services for the frontend.
     * capacity = sum of resource daily capacities — 0 if no resources configured.
     * React uses this to decide whether to show a custom-capacity input.
     */
    private function mapServices($services): \Illuminate\Support\Collection
    {
        return $services->map(fn ($s) => [
            'id'       => $s->id,
            'label'    => $s->label,
            'picture'  => $s->picture,
            // capacity is stored weekly — convert to daily (÷5) for load rate display
            'capacity' => round($s->Ressources->sum('capacity') / \App\Models\Methods\MethodsRessources::WORKING_DAYS_PER_WEEK, 2),
        ])->values();
    }

    private function countTaskNullRessource()
    {
        return Task::whereNotNull('order_lines_id')->whereDoesntHave('resources')->count();
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
