<?php

namespace App\Http\Controllers\Quality;

use App\Http\Controllers\Controller;
use App\Models\Quality\ProcessDiagram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProcessDiagramController extends Controller
{
    public function indexView()
    {
        $endpoints = [
            'index'  => route('quality.process-diagrams.index'),
            'store'  => route('quality.process-diagrams.store'),
            'show'   => route('quality.process-diagrams.show',   ['id' => '__ID__']),
            'update' => route('quality.process-diagrams.update', ['id' => '__ID__']),
            'delete' => route('quality.process-diagrams.delete', ['id' => '__ID__']),
        ];

        return view('quality.quality-process-diagrams', compact('endpoints'));
    }

    public function index()
    {
        $diagrams = ProcessDiagram::with('creator:id,name')
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'name', 'description', 'created_by', 'created_at', 'updated_at']);

        return response()->json($diagrams);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'nodes'       => ['nullable', 'array'],
            'edges'       => ['nullable', 'array'],
        ]);

        $diagram = ProcessDiagram::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return response()->json($diagram, 201);
    }

    public function show($id)
    {
        $diagram = ProcessDiagram::with('creator:id,name')->findOrFail($id);
        return response()->json($diagram);
    }

    public function update(Request $request, $id)
    {
        $diagram = ProcessDiagram::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'nodes'       => ['nullable', 'array'],
            'edges'       => ['nullable', 'array'],
        ]);

        $diagram->update($validated);

        return response()->json($diagram);
    }

    public function delete($id)
    {
        $diagram = ProcessDiagram::findOrFail($id);
        $diagram->delete();
        return response()->json(['message' => 'Supprimé.']);
    }
}
