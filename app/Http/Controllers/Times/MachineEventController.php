<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Times\TimesMachineEvent;
use App\Http\Requests\Times\StoreMachineEventRequest;
use App\Http\Requests\Times\UpdateMachineEventRequest;

class MachineEventController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreMachineEventRequest $request)
    {
        $TimesMachineEvent = TimesMachineEvent::create($request->only('code', 'ordre', 'label', 'color', 'etat'));
        if($request->mask_time) $TimesMachineEvent->mask_time=1;
        else $TimesMachineEvent->mask_time = 2;
        $TimesMachineEvent->save();

        return redirect()->route('times')->with('success', 'Successfully created machine event type.');
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateMachineEventRequest $request)
    {
        $MachineEvent = TimesMachineEvent::find($request->id);
        $MachineEvent->ordre=$request->ordre;
        $MachineEvent ->label=$request->label;

        if($request->mask_time_event_update) $MachineEvent->mask_time=1;
        else $MachineEvent->mask_time = 2;

        $MachineEvent->color=$request->color;
        $MachineEvent->etat=$request->etat;
        $MachineEvent->save();
        return redirect()->route('times')->with('success', 'Successfully updated machine event type.');
    }
}
