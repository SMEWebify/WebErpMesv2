<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Exports\InspectionMeasuresExport;
use App\Services\DocumentCodeGenerator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Inspection\InspectionProject;

class InspectionProjectController extends Controller
{
    protected $documentCodeGenerator;

    public function __construct(DocumentCodeGenerator $documentCodeGenerator)
    {
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = InspectionProject::query();

        if ($request->filled('companies_id')) {
            $query->where('companies_id', $request->input('companies_id'));
        }

        if ($request->filled('orders_id')) {
            $query->where('orders_id', $request->input('orders_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($projects);
    }

    public function indexView()
    {
        $endpoints = [
            'projectsIndex'       => route('inspection.projects.index'),
            'projectStore'        => route('inspection.projects.store'),
            'projectShow'         => route('inspection.projects.show',            ['id' => '__ID__']),
            'projectUpdate'       => route('inspection.projects.update',          ['id' => '__ID__']),
            'projectDocuments'    => route('inspection.projects.documents.store', ['id' => '__ID__']),
            'projectControlPoints'=> route('inspection.projects.points.store',    ['id' => '__ID__']),
            'controlPointUpdate'  => route('inspection.points.update',            ['id' => '__ID__']),
            'controlPointDelete'  => route('inspection.points.destroy',           ['id' => '__ID__']),
            'projectSessions'     => route('inspection.projects.sessions.store',  ['id' => '__ID__']),
            'sessionSubmit'       => route('inspection.sessions.submit',          ['id' => '__ID__']),
            'sessionClose'        => route('inspection.sessions.close',           ['id' => '__ID__']),
            'exportPdf'           => route('inspection.projects.export.pdf',      ['id' => '__ID__']),
            'exportXlsx'          => route('inspection.projects.export.xlsx',     ['id' => '__ID__']),
        ];

        return view('quality/quality-inspection-projects', compact('endpoints'));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $project = InspectionProject::with(['Documents', 'ControlPoints', 'MeasureSessions', 'NonConformities'])
            ->findOrFail($id);

        return response()->json($project);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'companies_id' => ['required', 'integer'],
            'orders_id' => ['nullable', 'integer'],
            'order_lines_id' => ['nullable', 'integer'],
            'of_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draft,active,closed,archived'],
            'quantity_planned' => ['nullable', 'integer', 'min:0'],
            'serial_tracking' => ['nullable', 'boolean'],
        ]);

        $lastProject = InspectionProject::orderBy('id', 'desc')->first();
        $code = $this->documentCodeGenerator->generateDocumentCode('inspection-projects', $lastProject ? $lastProject->id : 0);

        $project = InspectionProject::create(array_merge($validated, [
            'code' => $code,
            'created_by' => Auth::id(),
        ]));

        return response()->json($project, 201);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $project = InspectionProject::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'companies_id' => ['sometimes', 'integer'],
            'orders_id' => ['nullable', 'integer'],
            'order_lines_id' => ['nullable', 'integer'],
            'of_id' => ['nullable', 'integer'],
            'status' => ['sometimes', 'in:draft,active,closed,archived'],
            'quantity_planned' => ['nullable', 'integer', 'min:0'],
            'serial_tracking' => ['nullable', 'boolean'],
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportXlsx($id)
    {
        $project = InspectionProject::findOrFail($id);

        return Excel::download(new InspectionMeasuresExport($project->id), $project->code . '-mesures.xlsx');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf($id)
    {
        $project = InspectionProject::with(['Documents', 'ControlPoints', 'MeasureSessions.Measures', 'NonConformities'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('inspection/inspection-report', [
            'project' => $project,
        ]);

        return $pdf->download($project->code . '-rapport.pdf');
    }
}
