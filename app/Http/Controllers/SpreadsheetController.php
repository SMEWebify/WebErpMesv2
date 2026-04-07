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
            'sheets' => ['nullable', 'array'],
            'sheets.*.id' => ['nullable', 'integer'],
            'sheets.*.name' => ['nullable', 'string', 'max:255'],
            'sheets.*.data' => ['nullable', 'array'],
            'snapshot' => ['nullable', 'array'],
            'snapshot.sheets' => ['nullable', 'array'],
        ]);

        $sheetsPayload = $validated['sheets'] ?? $this->extractSheetsFromSnapshot($validated['snapshot'] ?? null);

        if (empty($sheetsPayload)) {
            return response()->json([
                'message' => 'Aucune feuille à sauvegarder.',
                'errors' => ['sheets' => ['Le tableur ne contient aucune feuille valide.']],
            ], 422);
        }

        foreach ($sheetsPayload as $index => $sheetData) {
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

    private function extractSheetsFromSnapshot(?array $snapshot): array
    {
        if (empty($snapshot['sheets']) || !is_array($snapshot['sheets'])) {
            return [];
        }

        $sheetOrder = is_array($snapshot['sheetOrder'] ?? null)
            ? $snapshot['sheetOrder']
            : array_keys($snapshot['sheets']);

        $sheets = [];
        foreach ($sheetOrder as $sheetKey) {
            $sheetData = $snapshot['sheets'][$sheetKey] ?? null;

            if (!is_array($sheetData)) {
                continue;
            }

            $sheets[] = [
                'name' => $sheetData['name'] ?? null,
                'data' => $sheetData,
            ];
        }

        return $sheets;
    }
}
