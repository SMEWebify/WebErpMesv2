<?php

namespace App\Http\Controllers\Planning;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Builder;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve start and end dates from the query
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->addMonths(1)->format('Y-m-d')); // Default, 1 month from today

        // Retrieve the state of the display_hours_diff checkbox
        $displayHoursDiff = $request->input('display_hours_diff', false);

        // Check that the start date is not greater than the end date
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            return redirect()->route('production.load.planning')->withErrors(['The start date must be before or equal to the end date.']);
        }

        // Retrieve tasks and services
        $taches = $this->getTasks($startDate, $endDate);
        $services = $this->getServices();

        // Check if there are no tasks
        if ($taches->isEmpty() && $this->countTaskNullRessource() < 1) {
            return redirect()->route('production.task')->with('error', 'No task in planning');
        }

        // Calculate hours worked and tasks per service per day
        [$hoursWorkedPerServiceDay, $tasksPerServiceDay] = $this->calculateHoursAndTasks($taches);

        // Calculate load rates per service per day
        $rateChargePerServiceDay = $this->calculateLoadRates($hoursWorkedPerServiceDay);

        // Create a data structure for load rates
        $structureRateLoad = $this->createLoadRateStructure($rateChargePerServiceDay);

        // Generate all possible dates between the start and end dates
        $possibleDates = $this->generatePossibleDates($startDate, $endDate);

        // Count tasks with null end date
        $countTaskNullDate = $this->countTaskNullDate();

        // Count tasks with null resources
        $countTaskNullRessource = $this->countTaskNullRessource();

        return view('workflow/planning-index', compact('taches', 'countTaskNullRessource', 'countTaskNullDate', 'tasksPerServiceDay', 'structureRateLoad', 'services', 'possibleDates', 'startDate', 'endDate', 'displayHoursDiff'));
    }

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
        return MethodsServices::where(function (Builder $query) {
                                    return $query->where('type', 1)
                                                ->orWhere('type', 7);
                                })->get();
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

    private function calculateHoursAndTasks($taches)
    {
        $hoursWorkedPerServiceDay = [];
        $tasksPerServiceDay = [];

        foreach ($taches as $tache) {
            $serviceId = $tache['methods_services_id'];
            $jour = (new Carbon($tache['end_date']))->format('Y-m-d'); // Convert the date to Y-m-d format

            // Calculate hours worked
            if (!isset($hoursWorkedPerServiceDay[$serviceId])) {
                $hoursWorkedPerServiceDay[$serviceId] = [];
            }
            if (!isset($hoursWorkedPerServiceDay[$serviceId][$jour])) {
                $hoursWorkedPerServiceDay[$serviceId][$jour] = $tache->TotalTime();
            } else {
                $hoursWorkedPerServiceDay[$serviceId][$jour] += $tache->TotalTime();
            }

            // Collect tasks per service per day
            if (!isset($tasksPerServiceDay[$serviceId])) {
                $tasksPerServiceDay[$serviceId] = [];
            }
            if (!isset($tasksPerServiceDay[$serviceId][$jour])) {
                $tasksPerServiceDay[$serviceId][$jour] = [];
            }
            $tasksPerServiceDay[$serviceId][$jour][] = $tache->id;
        }

        return [$hoursWorkedPerServiceDay, $tasksPerServiceDay];
    }

    private function calculateLoadRates($hoursWorkedPerServiceDay)
    {
        $rateChargePerServiceDay = [];
        $capacityHebdoService = 16; // Hypothetical weekly capacity of 16 hours

        foreach ($hoursWorkedPerServiceDay as $serviceId => $hoursPerDay) {
            foreach ($hoursPerDay as $jour => $HoursWorked) {
                $chargeRate = ($HoursWorked / $capacityHebdoService) * 100;
                $rateChargePerServiceDay[$serviceId][$jour] = $chargeRate;
            }
        }

        return $rateChargePerServiceDay;
    }

    private function createLoadRateStructure($rateChargePerServiceDay)
    {
        $structureRateLoad = [];

        foreach ($rateChargePerServiceDay as $serviceId => $tauxParJour) {
            foreach ($tauxParJour as $jour => $taux) {
                $structureRateLoad[$jour][$serviceId] = $taux;
            }
        }

        return $structureRateLoad;
    }

    private function generatePossibleDates($startDate, $endDate)
    {
        $possibleDates = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $possibleDates[] = $currentDate;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return $possibleDates;
    }
}
