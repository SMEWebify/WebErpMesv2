<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Events\AndonAlertTriggered;
use App\Http\Controllers\Controller;
use App\Models\Planning\AndonAlerts;
use Illuminate\Support\Facades\Auth;

class AndonAlertController extends Controller
{
    public function triggerAlert(Request $request)
    {
        $alert = AndonAlerts::create([
            'task_id' => $request->task_id,
            'methods_ressources_id' => $request->resource_id,
            'type' => $request->type,
            'description' => $request->description,
            'status' => 1,
            'user_id' => Auth::id(),
            'triggered_at' => now(),
        ]);
        $alertData= ['message' => 'Une alerte Andon a été déclenchée'];
        // Émettre l'événement
        broadcast(new AndonAlertTriggered($alert));

        return redirect()->back()->with('success', 'Andon alert triggered successfully.');
    }

    public function resolveAlert($id)
    {
        $alert = AndonAlerts::findOrFail($id);
        $alert->markAsResolved(auth()->id());
        
        // Émettre l'événement
        broadcast(new AndonAlertTriggered($alert));

        return redirect()->back();
    }

    public function inProgressAlert($id)
    {
        $alert = AndonAlerts::findOrFail($id);
        $alert->markinProgressAlert(auth()->id());
        
        // Émettre l'événement
        broadcast(new AndonAlertTriggered($alert));

        return redirect()->back();
    }

    public function dashboard()
    {
        $andonAlerts = AndonAlerts::orderByRaw("FIELD(status, '1', '2', '3')")->orderByDesc('id')->get();
        return view('workshop/workshop-andon', compact('andonAlerts'));
    }
}
