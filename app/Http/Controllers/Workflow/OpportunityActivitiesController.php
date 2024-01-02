<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OpportunitiesActivitiesLogs;
use App\Http\Requests\Workflow\StoreOpportunityActivityRequest;
use App\Http\Requests\Workflow\UpdateOpportunityActivityRequest;

class OpportunityActivitiesController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreOpportunityActivityRequest $request)
    {
        $Activity =  OpportunitiesActivitiesLogs::create($request->only('opportunities_id','label', 'type','priority','due_date', 'comment'));
        return redirect()->route('opportunities.show', ['id' =>  $Activity->opportunities_id])->with('success', 'Successfully created activity.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateOpportunityActivityRequest $request)
    {
        $Activity = OpportunitiesActivitiesLogs::find($request->id);
        $Activity->label=$request->label;
        $Activity->type=$request->type;
        $Activity->statu=$request->statu;
        $Activity->priority=$request->priority;
        $Activity->due_date=$request->due_date;
        $Activity->comment=$request->comment;
        $Activity->save();

        return redirect()->route('opportunities.show', ['id' =>  $Activity->opportunities_id])->with('success', 'Successfully updated activity.');
    }
}
