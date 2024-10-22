<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Http\Requests\Workflow\StoreOpportunityEventRequest;
use App\Http\Requests\Workflow\UpdateOpportunityEventRequest;

class OpportunityEventsController extends Controller
{
    /**
     * @param \App\Http\Requests\Workflow\StoreOpportunityEventRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOpportunityEventRequest $request)
    {
        $Event = OpportunitiesEventsLogs::create($request->validated());
        return redirect()->route('opportunities.show', ['id' => $Event->opportunities_id])->with('success', 'Successfully created event.');
    }

    /**
     * @param \App\Http\Requests\Workflow\UpdateOpportunityEventRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOpportunityEventRequest $request)
    {
        $Event = OpportunitiesEventsLogs::findOrFail($request->id);
        $Event->update($request->validated());

        return redirect()->route('opportunities.show', ['id' => $Event->opportunities_id])->with('success', 'Successfully updated event.');
    }
}