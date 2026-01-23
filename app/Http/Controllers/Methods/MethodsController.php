<?php

namespace App\Http\Controllers\Methods;

use App\Services\SelectDataService;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsServices;

class MethodsController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('methods/methods-index');
    }

    public function overview()
    {
        $statusTitles = ['Started', 'In progress'];

        $sections = MethodsSection::query()
            ->with(['Ressources' => function ($query) use ($statusTitles) {
                $query->with([
                    'service',
                    'locations',
                    'tasks' => function ($taskQuery) use ($statusTitles) {
                        $taskQuery->whereHas('status', function ($statusQuery) use ($statusTitles) {
                            $statusQuery->whereIn('title', $statusTitles);
                        })
                        ->with(['status', 'Products'])
                        ->orderBy('due_date');
                    },
                ])
                ->orderBy('ordre');
            }])
            ->orderBy('ordre')
            ->get();

        $services = MethodsServices::query()
            ->withCount('Ressources')
            ->withCount(['Tasks as tasks_in_progress_count' => function ($query) use ($statusTitles) {
                $query->whereHas('status', function ($statusQuery) use ($statusTitles) {
                    $statusQuery->whereIn('title', $statusTitles);
                });
            }])
            ->orderBy('ordre')
            ->get();

        return view('methods/methods-overview', compact('sections', 'services'));
    }
}
