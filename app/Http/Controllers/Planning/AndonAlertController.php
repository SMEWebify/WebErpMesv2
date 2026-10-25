<?php

namespace App\Http\Controllers\Planning;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Events\AndonAlertTriggered;
use App\Services\OrderLinesService;
use App\Http\Controllers\Controller;
use App\Models\Planning\AndonAlerts;
use Illuminate\Support\Facades\Auth;
use App\Models\Planning\TaskActivities;

class AndonAlertController extends Controller
{
    protected $orderLinesService;

    public function __construct(OrderLinesService $orderLinesService)
    {
        $this->orderLinesService = $orderLinesService;
    }
    
    public function triggerAlert(Request $request)
    {
        $alert = AndonAlerts::create([
            'task_id' => $request->task_id,
            // Sans ressource affectée, l'écran envoyait la ressource n° 1 : l'alerte partait sur
            // une machine au hasard. Une valeur vide reste vide.
            'methods_ressources_id' => $request->filled('resource_id') ? $request->resource_id : null,
            'type' => $request->type,
            // L'écran opérateur envoyait « message » : la description saisie était perdue.
            'description' => $request->input('description', $request->input('message')),
            'status' => 1,
            'user_id' => Auth::id(),
            'triggered_at' => now(),
        ]);

        // Émettre l'événement
        broadcast(new AndonAlertTriggered($alert));

        return redirect()->back()->with('success', 'Andon alert triggered successfully.');
    }

    public function resolveAlert($id)
    {
        $alert = AndonAlerts::findOrFail($id);
        $alert->markAsResolved(Auth::id());
        
        // Émettre l'événement
        broadcast(new AndonAlertTriggered($alert));

        return redirect()->back();
    }

    public function inProgressAlert($id)
    {
        $alert = AndonAlerts::findOrFail($id);
        $alert->markinProgressAlert(Auth::id());
        
        // Émettre l'événement
        broadcast(new AndonAlertTriggered($alert));

        return redirect()->back();
    }

    public function taskAlertsDashboard()
    {
        $andonAlerts = AndonAlerts::orderByRaw("FIELD(status, '1', '2', '3')")->orderByDesc('id')->get();
        return view('workshop/workshop-andon', compact('andonAlerts'));
    }

    public function taskActivityDashboard()
    {
        $taskActivities = TaskActivities::where('timestamp', '>=', Carbon::now()->subDay())
                                        ->orderByDesc('id')
                                        ->get();
        return view('workshop/workshop-andon-task-activity', compact('taskActivities'));
    }

    public function orderWorkshopDashboard()
    {
        return view('workshop/workshop-andon-orders', [
            'incomingOrders'      => $this->orderLinesService->getIncomingOrders(),
            'lateOrders'          => $this->orderLinesService->getLateOrders(),
            'readyOrders'         => $this->orderLinesService->getReadyOrders(),
        ]);
    }
}
