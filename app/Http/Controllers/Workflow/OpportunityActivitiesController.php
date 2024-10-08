<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\OpportunitiesActivitiesLogs;
use App\Http\Requests\Workflow\StoreOpportunityActivityRequest;
use App\Http\Requests\Workflow\UpdateOpportunityActivityRequest;

class OpportunityActivitiesController extends Controller
{
    /**
     * @param \App\Http\Requests\StoreOpportunityActivityRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOpportunityActivityRequest $request)
    {
        $Activity = OpportunitiesActivitiesLogs::create($request->validated());
        return redirect()->route('opportunities.show', ['id' => $Activity->opportunities_id])->with('success', 'Successfully created activity.');
    }

    /**
     * @param \App\Http\Requests\UpdateOpportunityActivityRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOpportunityActivityRequest $request)
    {
        $Activity = OpportunitiesActivitiesLogs::findOrFail($request->id);
        $Activity->update($request->validated());

        return redirect()->route('opportunities.show', ['id' => $Activity->opportunities_id])->with('success', 'Successfully updated activity.');
    }
}
