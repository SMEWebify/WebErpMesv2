<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportCsvService;
use App\Services\Products\ProductCatalogAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Assistant d'import de l'écran Imports/Exports (React ImportWizard) :
 * dépôt du fichier → correspondance des colonnes et aperçu simulé → import.
 *
 * Le fichier déposé est gardé dans le stockage privé (imports/{utilisateur})
 * le temps de l'assistant, désigné par un jeton : jamais sous public/, jamais
 * lisible par un autre utilisateur. Il est supprimé après l'import, et ceux
 * de plus de config('imports.temporary_hours') au dépôt suivant.
 */
class DataImportController extends Controller
{
    public function __construct(private ImportCsvService $imports)
    {
    }

    /**
     * Dépôt : lit le fichier et renvoie colonnes, premières lignes et
     * correspondance proposée.
     */
    public function preview(Request $request, string $type): JsonResponse
    {
        $this->authorizeType($request, $type);

        $file = $request->file('file');
        if ($error = $this->imports->fileError($file)) {
            return response()->json(['message' => $error], 422);
        }

        try {
            $rows = $this->imports->readFile($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => __('imports.unreadable', ['message' => $e->getMessage()])], 422);
        }

        // Lignes non vides, avec leur numéro dans le fichier (celui des rejets).
        $rows = array_filter($rows, fn ($row) => $row !== []);
        if ($rows === []) {
            return response()->json(['message' => __('imports.empty_file')], 422);
        }

        $this->purgeOldFiles($request);
        $token = (string) Str::uuid();
        $file->storeAs($this->folder($request), $token . '.' . strtolower($file->getClientOriginalExtension()), 'local');

        $width   = max(array_map('count', $rows));
        $headers = array_pad(reset($rows), $width, null);
        $sample  = [];
        foreach (array_slice($rows, 0, config('imports.preview_rows') + 1, true) as $index => $row) {
            $sample[] = ['line' => $index + 1, 'cells' => array_pad($row, $width, null)];
        }

        $columns = [];
        for ($i = 0; $i < $width; $i++) {
            $columns[] = [
                'index'  => $i,
                'letter' => Coordinate::stringFromColumnIndex($i + 1),
                'header' => $headers[$i] !== null ? trim((string) $headers[$i]) : null,
            ];
        }

        return response()->json([
            'token'    => $token,
            'filename' => $file->getClientOriginalName(),
            'columns'  => $columns,
            'rows'     => $sample,
            'total'    => count($rows),
            'fields'   => $this->imports->fields($type),
            'mapping'  => $this->imports->suggestMapping($type, $headers),
        ]);
    }

    /**
     * Simulation : statut des lignes de l'aperçu et rejets, rien n'est écrit.
     */
    public function check(Request $request, string $type): JsonResponse
    {
        return $this->handle($request, $type, true);
    }

    public function run(Request $request, string $type): JsonResponse
    {
        return $this->handle($request, $type, false);
    }

    /**
     * Modèle Excel : une ligne d'en-tête avec le libellé de chaque champ, que
     * l'assistant reconnaît tel quel.
     */
    public function template(Request $request, string $type)
    {
        $this->authorizeType($request, $type);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($this->imports->fields($type) as $i => $field) {
            $sheet->setCellValue([$i + 1, 1], $field['label']);
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $label = __("imports.wizard.types.$type.label");

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            Str::slug(__('imports.wizard.template') . ' ' . $label) . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function handle(Request $request, string $type, bool $dryRun): JsonResponse
    {
        $this->authorizeType($request, $type);

        $validated = $request->validate([
            'token'   => 'required|uuid',
            'mapping' => 'array',
            'header'  => 'boolean',
            'options' => 'array',
            'options.methods_services_id' => 'nullable|exists:methods_services,id',
            'options.methods_families_id' => 'nullable|exists:methods_families,id',
            'options.methods_units_id'    => 'nullable|exists:methods_units,id',
            'options.purchased'           => 'nullable|in:1,2',
            'options.sold'                => 'nullable|in:1,2',
            'filename' => 'nullable|string|max:255',
        ]);

        $path = $this->storedFile($request, $validated['token']);
        if ($path === null) {
            return response()->json(['message' => __('imports.expired')], 410);
        }

        $rows   = $this->imports->readFile(Storage::disk('local')->path($path), pathinfo($path, PATHINFO_EXTENSION));
        $result = $this->imports->process(
            $type,
            $rows,
            $validated['mapping'] ?? [],
            (bool) ($validated['header'] ?? true),
            $this->options($type, $validated['options'] ?? []),
            $dryRun,
            config('imports.preview_rows'),
            $validated['filename'] ?? null,
        );

        if ($result['error'] !== null) {
            return response()->json(['message' => $result['error']], 422);
        }

        if (!$dryRun) {
            Storage::disk('local')->delete($path);
        }

        return response()->json([
            'created'  => $result['created'],
            'updated'  => $result['updated'],
            'rejected' => $result['rejected'],
            'rejects'  => array_slice($result['rejects'], 0, 200),
            'lines'    => (object) $result['lines'],
            'message'  => $dryRun ? null : $this->imports->resultMessage($result),
        ]);
    }

    /** Options reconnues par type : jamais d'identifiant de document parent ici. */
    private function options(string $type, array $options): array
    {
        return $type === 'products'
            ? array_intersect_key($options, array_flip(['methods_services_id', 'methods_families_id', 'methods_units_id', 'purchased', 'sold']))
            : [];
    }

    private function authorizeType(Request $request, string $type): void
    {
        abort_unless(in_array($type, ImportCsvService::WIZARD_TYPES, true), 404);
        // Écrire la base article est réservé à products-edit, comme l'import d'articles d'avant.
        abort_if($type === 'products' && !ProductCatalogAccess::canEdit($request->user()), 403);
    }

    private function folder(Request $request): string
    {
        return 'imports/' . $request->user()->id;
    }

    private function storedFile(Request $request, string $token): ?string
    {
        foreach (Storage::disk('local')->files($this->folder($request)) as $path) {
            if (pathinfo($path, PATHINFO_FILENAME) === $token) {
                return $path;
            }
        }

        return null;
    }

    private function purgeOldFiles(Request $request): void
    {
        $limit = now()->subHours(config('imports.temporary_hours'))->getTimestamp();
        $disk = Storage::disk('local');
        foreach ($disk->files($this->folder($request)) as $path) {
            if ($disk->lastModified($path) < $limit) {
                $disk->delete($path);
            }
        }
    }
}
