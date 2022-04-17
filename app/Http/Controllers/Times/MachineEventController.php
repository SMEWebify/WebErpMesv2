<?php

namespace App\Http\Controllers\Times;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Times\TimesMachineEvent;
use App\Http\Requests\Times\StoreMachineEventRequest;

class MachineEventController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreMachineEventRequest $request)
    {
        $TimesMachineEvent = TimesMachineEvent::create($request->only('code', 'ordre', 'label', 'mask_time', 'color', 'ETAT'));
        return redirect()->route('times')->with('success', 'Successfully created machine event type.');
    }
}
