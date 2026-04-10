<?php

namespace App\Http\Controllers\Methods;

use App\Services\SelectDataService;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsServices;
use Illuminate\Support\Facades\Lang;

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

        // Append computed progress to each task
        $sections->each(function ($section) {
            $section->Ressources->each(function ($resource) {
                $resource->tasks->each(function ($task) {
                    $task->progress = $task->progress();
                });
            });
        });

        $allTrans = Lang::get('general_content');
        $transKeys = [
            'service_trans_key', 'ressource_trans_key', 'in_progress_trans_key',
            'location_trans_key', 'location_in_workshop_trans_key',
            'tasks_trans_key', 'no_data_trans_key', 'no_task_trans_key',
            'no_section_trans_key', 'view_trans_key', 'progress_trans_key',
        ];
        $trans = collect($transKeys)
            ->mapWithKeys(fn ($k) => [$k => $allTrans[$k] ?? $k])
            ->all();

        return view('methods/methods-overview', compact('sections', 'services', 'trans'));
    }
}
