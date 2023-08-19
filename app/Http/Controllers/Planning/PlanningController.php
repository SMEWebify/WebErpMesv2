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
    public function index()
    {
        // Collect Tasks
        $taches = Task::with('service')
                        ->where('end_date', '>=', now())
                        ->whereNotNull('order_lines_id')
                        ->where(function (Builder $query) {
                            return $query->where('tasks.type', 1)
                                        ->orWhere('tasks.type', 7);
                        })->get();
        
        if($taches->count() < 1){
            return redirect()->route('production.task')->with('error', 'No tosk in planning');
        }

        $countTaskNullDate = Task::whereNull('end_date')
                                ->whereNotNull('order_lines_id')
                                ->where(function (Builder $query) {
                                    return $query->where('tasks.type', 1)
                                                ->orWhere('tasks.type', 7);
                                })
                                ->get();
        $countTaskNullDate = $countTaskNullDate->count();
        // Collect service
        $services = MethodsServices::where(function (Builder $query) {
                                            return $query->where('type', 1)
                                                        ->orWhere('type', 7);
                                        })->get();

        // Array to store the hours worked by service and by day
        $hoursWorkedPerServiceDay = [];
        // Array to store tasks by department and day
        $tasksPerServiceDay = [];

        // Browse tasks and calculate hours worked for each service and day
        foreach ($taches as $tache) {
            $serviceId = $tache['methods_services_id'];
            $jour = (new Carbon($tache['end_date']))->format('Y-m-d'); // Convert the date to Y-m-d format
            
            // Check if the service already exists in the array
            if (!isset($hoursWorkedPerServiceDay[$serviceId])) {
                $hoursWorkedPerServiceDay[$serviceId] = [];
            }

            // Add the hours worked to the existing sum for the service and the day
            if (!isset($hoursWorkedPerServiceDay[$serviceId][$jour])) {
                $hoursWorkedPerServiceDay[$serviceId][$jour] = $tache->TotalTime();
            } else {
                $hoursWorkedPerServiceDay[$serviceId][$jour] += $tache->TotalTime();
            }

            // Section for add task id in tooltip cell
            // Check if the service already exists in the array
            if (!isset($tasksPerServiceDay[$serviceId])) {
                $tasksPerServiceDay[$serviceId] = [];
            }

            // Check if the day already exists in the array
            if (!isset($tasksPerServiceDay[$serviceId][$jour])) {
                $tasksPerServiceDay[$serviceId][$jour] = [];
            }

            /// Add the task ID to the table corresponding to the service and the day
            $tasksPerServiceDay[$serviceId][$jour][] = $tache->id;
        }

        // Array to store load rates per service and per day
        $rateChargePerServiceDay = [];

        /// Browse the hours worked by service and by day and calculate the load rates
        foreach ($hoursWorkedPerServiceDay as $serviceId => $hoursPerDay) {
            foreach ($hoursPerDay as $jour => $HoursWorked) {
                //currently the capacity is fixed, see in the future
                $capacityHebdoService = 16; // Hypothetical weekly capacity of 16 hours

                // Calculate the percentage charge rate
                $chargeRate = ($HoursWorked / $capacityHebdoService) * 100;

                // Store the charge rate in the array
                $rateChargePerServiceDay[$serviceId][$jour] = $chargeRate;
            }
        }

        // Create a data structure for load rates
        $structureRateLoad = [];
        foreach ($rateChargePerServiceDay as $serviceId => $tauxParJour) {
            foreach ($tauxParJour as $jour => $taux) {
                $structureRateLoad[$jour][$serviceId] = $taux;
            }
        }

        // Extract all unique dates from each array into $rateChargePerServiceDay
        $allDatesUniques = [];
        foreach ($rateChargePerServiceDay as $ratePerService) {
            $datesService = array_keys($ratePerService);
            $allDatesUniques = array_merge($allDatesUniques, $datesService);
        }

        // Remove duplicates and sort unique dates
        $allDatesUniques = array_unique($allDatesUniques);
        sort($allDatesUniques);

        // Get smallest and largest date
        $minDate = min($allDatesUniques);
        $maxDate = max($allDatesUniques);

        // Generate all dates between the smallest and largest date
        $possibleDates = [];
        $currentDate = $minDate;
        while ($currentDate <= $maxDate) {
            $possibleDates[] = $currentDate;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        
        return view('workflow/planning-index', compact('taches', 'countTaskNullDate', 'tasksPerServiceDay', 'structureRateLoad', 'services', 'possibleDates'));
    }
}
