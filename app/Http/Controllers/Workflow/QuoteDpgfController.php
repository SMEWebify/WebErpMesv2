<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Quotes;
use App\Services\ImportCsvService;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Imports\TemporaryImportFile;
use App\Services\Quotes\DpgfImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Import d'un DPGF dans un devis ouvert : dépôt du classeur → aperçu (feuille,
 * en-tête, colonnes, type de chaque ligne), recalculé à chaque choix de
 * l'utilisateur. Rien n'est écrit ici ; la création des lignes relues est
 * QuoteLinesController::storeDpgfJson.
 */
class QuoteDpgfController extends Controller
{
    public function __construct(
        private readonly DpgfImporter $importer,
        private readonly SpreadsheetReader $reader,
        private readonly TemporaryImportFile $files,
        private readonly ImportCsvService $imports,
    ) {
    }

    public function preview(Request $request, int $quoteId): JsonResponse
    {
        $this->openQuote($quoteId);

        $file = $request->file('file');
        if ($error = $this->imports->fileError($file)) {
            return response()->json(['message' => $error], 422);
        }

        $token = $this->files->put($file, $request->user()->id);
        $path = $this->files->find($token, $request->user()->id);

        return $this->analyseFile($path, 0, null, null, [
            'token' => $token,
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function analyse(Request $request, int $quoteId): JsonResponse
    {
        $this->openQuote($quoteId);

        $validated = $request->validate([
            'token' => 'required|uuid',
            'sheet' => 'nullable|integer|min:0',
            'header_row' => 'nullable|integer|min:0',
            'columns' => 'nullable|array',
            'columns.*' => 'nullable|integer|min:0',
        ]);

        $path = $this->files->find($validated['token'], $request->user()->id);
        if ($path === null) {
            return response()->json(['message' => __('imports.expired')], 410);
        }

        return $this->analyseFile(
            $path,
            (int) ($validated['sheet'] ?? 0),
            array_key_exists('columns', $validated) ? ($validated['columns'] ?? []) : null,
            $validated['header_row'] ?? null,
            ['token' => $validated['token']],
        );
    }

    private function analyseFile(string $path, int $sheet, ?array $columns, ?int $headerRow, array $extra): JsonResponse
    {
        $absolute = $this->files->absolutePath($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        try {
            $sheets = $this->reader->sheetNames($absolute, $extension);
            if ($sheet >= count($sheets)) {
                $sheet = 0;
            }
            $rows = $this->reader->read($absolute, $extension, $sheet);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => __('imports.unreadable', ['message' => $e->getMessage()])], 422);
        }

        if (array_filter($rows, fn ($row) => $row !== []) === []) {
            return response()->json(['message' => __('imports.empty_file')], 422);
        }

        return response()->json($extra + [
            'sheets' => $sheets,
            'sheet' => $sheet,
            'truncated' => count($rows) > (int) config('dpgf.max_rows'),
        ] + $this->importer->analyse($rows, $columns, $headerRow));
    }

    private function openQuote(int $quoteId): Quotes
    {
        abort_unless(config('dpgf.enabled'), 404);
        $quote = Quotes::withTemplates()->findOrFail($quoteId);
        abort_if($quote->statu != 1, 403);

        return $quote;
    }
}
