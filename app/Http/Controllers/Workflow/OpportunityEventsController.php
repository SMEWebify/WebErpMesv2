<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Http\Requests\Workflow\StoreOpportunityEventRequest;
use App\Http\Requests\Workflow\UpdateOpportunityEventRequest;

class OpportunityEventsController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOpportunityEventRequest $request)
    {
        $Event =  OpportunitiesEventsLogs::create($request->only('opportunities_id','label', 'type','start_date','end_date', 'comment'));
        return redirect()->route('opportunities.show', ['id' =>  $Event->opportunities_id])->with('success', 'Successfully created event.');
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOpportunityEventRequest $request)
    {
        $Event = OpportunitiesEventsLogs::find($request->id);
        $Event->label=$request->label;
        $Event->type=$request->type;
        $Event->start_date=$request->start_date;
        $Event->end_date=$request->end_date;
        $Event->comment=$request->comment;
        $Event->save();

        return redirect()->route('opportunities.show', ['id' =>  $Event->opportunities_id])->with('success', 'Successfully updated event.');
    }
}