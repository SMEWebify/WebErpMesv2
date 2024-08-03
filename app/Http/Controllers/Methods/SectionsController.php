<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsSection;
use App\Http\Requests\Methods\StoreSectionRequest;
use App\Http\Requests\Methods\UpdateSectiontRequest;

class SectionsController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    
    /**
     * Display a listing of the section.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $MethodsSections = MethodsSection::orderBy('ordre')->get();
        $userSelect = $this->SelectDataService->getUsers();
        return view('methods/methods-sections', [
            'MethodsSections' => $MethodsSections,
            'userSelect' => $userSelect,
        ]);
    }
    
    /**
     * Store a newly created section in storage.
     *
     * @param \App\Http\Requests\Methods\StoreSectionRequest $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSectionRequest $request)
    {
        $Section = MethodsSection::create($request->only('ordre','code', 'label','user_id','color'));
        return redirect()->route('methods.section')->with('success', 'Successfully created section.');
    }

    /**
     * Update the specified section in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateSectiontRequest $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateSectiontRequest $request)
    {
        $section = MethodsSection::findOrFail($request->id);
        $section->update($request->only(['ordre', 'label', 'color', 'user_id']));
        return redirect()->route('methods.section')->with('success', 'Successfully updated section.');
    }
}
