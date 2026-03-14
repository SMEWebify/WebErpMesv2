<?php

namespace App\Http\Controllers;

use App\Models\Spreadsheet;
use Illuminate\Http\Request;

class SpreadsheetController extends Controller
{
    public function index()
    {
        // TODO: add role-based permission
        $spreadsheets = Spreadsheet::with('creator')
            ->where('created_by', auth()->id())
            ->latest()
            ->paginate(15);

        return view('spreadsheet.index', compact('spreadsheets'));
    }

    public function create()
    {
        // TODO: add role-based permission
        return view('spreadsheet.create');
    }

    public function store(Request $request)
    {
        // TODO: add role-based permission
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $spreadsheet = Spreadsheet::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $spreadsheet->sheets()->create([
            'name' => 'Sheet1',
            'order' => 0,
            'data' => null,
        ]);

        return redirect()->route('spreadsheet.edit', $spreadsheet)
            ->with('success', 'Tableur créé avec succès.');
    }

    public function edit(Spreadsheet $spreadsheet)
    {
        // TODO: add role-based permission
        abort_unless($spreadsheet->created_by === auth()->id(), 403);

        $spreadsheet->load('sheets');

        return view('spreadsheet.editor', compact('spreadsheet'));
    }

    public function update(Request $request, Spreadsheet $spreadsheet)
    {
        // TODO: add role-based permission
        abort_unless($spreadsheet->created_by === auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $spreadsheet->update($validated);

        return redirect()->route('spreadsheet.edit', $spreadsheet)
            ->with('success', 'Tableur mis à jour.');
    }

    public function destroy(Spreadsheet $spreadsheet)
    {
        // TODO: add role-based permission
        abort_unless($spreadsheet->created_by === auth()->id(), 403);

        $spreadsheet->delete();

        return redirect()->route('spreadsheet.index')
            ->with('success', 'Tableur supprimé.');
    }

    public function save(Request $request, Spreadsheet $spreadsheet)
    {
        // TODO: add role-based permission
        abort_unless($spreadsheet->created_by === auth()->id(), 403);

        $validated = $request->validate([
            'sheets' => ['required', 'array'],
            'sheets.*.id' => ['nullable', 'integer'],
            'sheets.*.name' => ['nullable', 'string', 'max:255'],
            'sheets.*.data' => ['nullable', 'array'],
        ]);

        foreach ($validated['sheets'] as $index => $sheetData) {
            $payload = [
                'name' => $sheetData['name'] ?? ('Sheet' . ($index + 1)),
                'order' => $index,
                'data' => $sheetData['data'] ?? null,
            ];

            if (!empty($sheetData['id'])) {
                $spreadsheet->sheets()->where('id', $sheetData['id'])->update($payload);
                continue;
            }

            $spreadsheet->sheets()->create($payload);
        }

        return response()->json(['success' => true]);
    }
}
