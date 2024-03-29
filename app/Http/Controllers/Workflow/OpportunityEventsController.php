<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Http\Requests\Workflow\StoreOpportunityEventRequest;
use App\Http\Requests\Workflow\UpdateOpportunityEventRequest;

class OpportunityEventsController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreOpportunityEventRequest $request)
    {
        $Event =  OpportunitiesEventsLogs::create($request->only('opportunities_id','label', 'type','start_date','end_date', 'comment'));
        return redirect()->route('opportunities.show', ['id' =>  $Event->opportunities_id])->with('success', 'Successfully created event.');
    }

    /**
     * @param $request
     * @return View
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