<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\PreOrder;
use App\Models\Workflow\PreOrderLine;
use App\Services\DocumentCodeGenerator;
use App\Services\InvoiceReportInterpreter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreOrdersController extends Controller
{
    public function __construct(protected DocumentCodeGenerator $documentCodeGenerator)
    {
    }

    public function index()
    {
        $preOrders = PreOrder::withCount('lines')
            ->with(['convertedOrder', 'importBatch', 'lines'])
            ->orderByDesc('id')
            ->paginate(25);

        $invoiceReportRows = collect();
        $invoiceReportReadError = null;

        try {
            $interpreter = app(InvoiceReportInterpreter::class);
            $invoiceReportRows = $interpreter->getReport();
        } catch (\RuntimeException $exception) {
            $invoiceReportReadError = $exception->getMessage();
        }

        return view('workflow.pre-orders-index', compact('preOrders', 'invoiceReportRows', 'invoiceReportReadError'));
    }

    
    public function upload(Request $request)
    {
        set_time_limit((int) config('pre_orders.python_timeout', 120) + 30);

        $data = $request->validate([
            'pdfs' => 'required|array|min:1',
            'pdfs.*' => [
                'required',
                'file',
                'max:20480',
                function (string $attribute, $file, $fail): void {
                    $extension = strtolower((string) $file->getClientOriginalExtension());
                    $mimeType = strtolower((string) $file->getMimeType());
                    if ($extension !== 'pdf' && ! str_contains($mimeType, 'pdf')) {
                        $fail('Le champ ' . $attribute . ' doit être un fichier de type : pdf.');
                    }
                },
            ],
        ]);
    
        $disk = config('filesystems.default');
        $inputPath = $this->resolveInputPath((string) config('pre_orders.input_path', 'pre-orders/input'));
    
        foreach ($data['pdfs'] as $pdfFile) {
            $originalName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBaseName = Str::slug($originalName, '_') ?: 'pre_order';
            $fileName = $safeBaseName . '_' . now()->format('Ymd_His_u') . '.pdf';
            $tmpPath = $pdfFile->getRealPath() ?: $pdfFile->getPathname();
            Storage::disk($disk)->put($inputPath . '/' . $fileName, fopen($tmpPath, 'r'));
        }
    
        $pythonPath = config('pre_orders.python_executable');
        $scriptPath = config('pre_orders.python_script');
    
        if ($pythonPath && $scriptPath) {
    
            $systemRoot = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\Windows';
            $javaHome   = config('pre_orders.java_home') ?: getenv('JAVA_HOME') ?: 'C:\Java\jdk-26';
            $javaBin    = rtrim($javaHome, '\\/') . '\bin';

            // Les services Windows n'héritent pas du PATH utilisateur → on injecte Java explicitement
            $currentPath = (string) getenv('PATH');
            if (! str_contains($currentPath, $javaBin)) {
                $currentPath = $javaBin . ';' . $currentPath;
            }

            $env = [
                'PYTHONHASHSEED'   => '0',
                'PYTHONIOENCODING' => 'utf-8',
                'JAVA_HOME'   => $javaHome,
                'PATH'        => $currentPath,
                'SystemRoot'  => (string) $systemRoot,
                'WINDIR'      => (string) (getenv('WINDIR') ?: $systemRoot),
                'SystemDrive' => (string) (getenv('SystemDrive') ?: 'C:'),
                'TEMP'        => (string) getenv('TEMP'),
                'TMP'         => (string) getenv('TMP'),
                'USERPROFILE' => (string) getenv('USERPROFILE'),
                'COMSPEC'     => (string) getenv('COMSPEC'),
            ];
    
            $result = Process::env($env)
                ->timeout((int) config('pre_orders.python_timeout', 120))
                ->path(dirname($scriptPath))
                ->run([$pythonPath, $scriptPath]);

            if (! $result->successful()) {
                return redirect()->route('pre-orders.index')->withErrors(
                    'PDF(s) envoyé(s), mais le traitement Python a échoué : ' .
                    trim($result->errorOutput() ?: $result->output())
                );
            }

            $scanExitCode = Artisan::call('preorders:scan-output', [
                '--path' => config('pre_orders.output_path', 'output'),
                '--pattern' => config('pre_orders.file_pattern', '*.csv'),
                '--done-path' => config('pre_orders.done_path', 'output/done'),
            ]);

            if ($scanExitCode !== 0) {
                return redirect()->route('pre-orders.index')->withErrors(
                    "PDF(s) traité(s), mais l'import des CSV a échoué. Vérifiez les logs de la commande preorders:scan-output."
                );
            }

            return redirect()->route('pre-orders.index')->with('success', __('general_content.preorders_pdf_processed_success_trans_key'));
        }
    
        return redirect()->route('pre-orders.index')->with('success', __('general_content.preorders_pdf_uploaded_success_trans_key'));
    }
    

    private function resolveInputPath(string $configuredPath): string
    {
        $normalizedPath = trim(str_replace('\\', '/', $configuredPath));

        if ($normalizedPath === '') {
            return 'pre-orders/input';
        }

        if (preg_match('/^[A-Za-z]:\//', $normalizedPath) === 1 || str_starts_with($normalizedPath, '/')) {
            $base = ltrim(str_replace('\\', '/', basename(rtrim($normalizedPath, '/'))), '/');
            return $base !== '' ? $base : 'pre-orders/input';
        }

        $normalizedPath = ltrim($normalizedPath, '/');

        if (str_starts_with($normalizedPath, 'storage/app/')) {
            $normalizedPath = substr($normalizedPath, strlen('storage/app/'));
        }

        return trim($normalizedPath, '/');
    }

    public function pdf(PreOrder $preOrder): StreamedResponse
    {
        $sourcePdfPath = $this->resolveSourcePdfPath($preOrder);

        if ($sourcePdfPath === null) {
            abort(404, 'PDF introuvable.');
        }

        return Storage::disk(config('filesystems.default'))->response(
            $sourcePdfPath,
            basename($sourcePdfPath),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($sourcePdfPath) . '"',
            ]
        );
    }


    public function show(PreOrder $preOrder)
    {
        $preOrder->load('lines.suggestedProduct', 'lines.linkedProduct', 'convertedOrder');

        $sourcePdfUrl = $this->resolveSourcePdfPath($preOrder)
            ? route('pre-orders.source-pdf', $preOrder)
            : null;

        $defaultLabel = 'Pré-commande du ' . $preOrder->created_at->format('d/m/Y');

        return view('workflow.pre-orders-show', [
            'preOrder' => $preOrder,
            'defaultLabel' => $defaultLabel,
            'companies' => Companies::orderBy('code')->get(),
            'users' => User::orderBy('name')->get(),
            'paymentConditions' => AccountingPaymentConditions::orderBy('code')->get(),
            'paymentMethods' => AccountingPaymentMethod::orderBy('code')->get(),
            'deliveries' => AccountingDelivery::orderBy('code')->get(),
            'units' => MethodsUnits::orderBy('code')->get(),
            'vats' => AccountingVat::orderBy('code')->get(),
            'defaultUnit' => MethodsUnits::where('default', 1)->first(),
            'defaultVat' => AccountingVat::where('default', 1)->first(),
            'defaultPaymentCondition' => AccountingPaymentConditions::where('default', 1)->first(),
            'defaultPaymentMethod' => AccountingPaymentMethod::where('default', 1)->first(),
            'defaultDelivery' => AccountingDelivery::where('default', 1)->first(),
            'generatedOrderCode' => $this->generateOrderCodeByType(1),
            'sourcePdfUrl' => $sourcePdfUrl,
        ]);
    }

    public function sourcePdf(PreOrder $preOrder): StreamedResponse
    {
        $sourcePdfPath = $this->resolveSourcePdfPath($preOrder);

        abort_if($sourcePdfPath === null, 404);

        return Storage::disk(config('filesystems.default'))->response(
            $sourcePdfPath,
            basename($sourcePdfPath),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($sourcePdfPath) . '"',
            ]
        );
    }


    public function matchArticles(PreOrder $preOrder)
    {
        $preOrder->load('lines');

        foreach ($preOrder->lines as $line) {
            $reference = trim((string) $line->reference);

            if ($reference === '') {
                $line->update([
                    'suggested_product_id' => null,
                    'matching_unit_price' => null,
                ]);

                continue;
            }

            $product = Products::query()
                ->where('code', $reference)
                ->first();

            $line->update([
                'suggested_product_id' => $product?->id,
                'matching_unit_price' => $product?->selling_price,
            ]);
        }

        return redirect()->route('pre-orders.show', $preOrder)->with('success', __('general_content.preorders_matching_done_success_trans_key'));
    }

    public function acceptMatching(PreOrder $preOrder, PreOrderLine $line)
    {
        abort_unless((int) $line->pre_order_id === (int) $preOrder->id, 404);

        if (! $line->suggested_product_id) {
            return redirect()->route('pre-orders.show', $preOrder)->withErrors('Aucun article suggéré sur cette ligne.');
        }

        $line->update([
            'linked_product_id' => $line->suggested_product_id,
            'matching_confirmed_at' => now(),
        ]);

        return redirect()->route('pre-orders.show', $preOrder)->with('success', 'Matching accepté pour la ligne ' . $line->row_index . '.');
    }

    public function acceptAllMatching(PreOrder $preOrder)
    {
        $updatedCount = PreOrderLine::query()
            ->where('pre_order_id', $preOrder->id)
            ->whereNotNull('suggested_product_id')
            ->whereNull('linked_product_id')
            ->update([
                'linked_product_id' => DB::raw('suggested_product_id'),
                'matching_confirmed_at' => now(),
            ]);

        if ($updatedCount === 0) {
            return redirect()->route('pre-orders.show', $preOrder)->withErrors('Aucune ligne à valider.');
        }

        return redirect()->route('pre-orders.show', $preOrder)->with('success', $updatedCount . ' ligne(s) validée(s) automatiquement.');
    }

    public function convert(Request $request, PreOrder $preOrder)
    {
        if ($preOrder->status === PreOrder::STATUS_CONVERTED) {
            return redirect()->route('pre-orders.show', $preOrder)->withErrors('Pré-commande déjà convertie.');
        }

        $data = $request->validate([
            'code' => 'nullable|string|max:255',
            'label' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'companies_id' => 'required_if:type,1|nullable|exists:companies,id',
            'customer_reference' => 'nullable|string|max:255',
            'validity_date' => 'nullable|date',
            'accounting_payment_conditions_id' => 'nullable|exists:accounting_payment_conditions,id',
            'accounting_payment_methods_id' => 'nullable|exists:accounting_payment_methods,id',
            'accounting_deliveries_id' => 'nullable|exists:accounting_deliveries,id',
            'comment' => 'nullable|string',
            'methods_units_id' => 'required|exists:methods_units,id',
            'accounting_vats_id' => 'required|exists:accounting_vats,id',
            'delivery_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'type' => 'required|in:1,2',
        ]);

        $data['code'] = $data['code'] ?? $this->generateOrderCodeByType((int) $data['type']);

        $preOrder->load('lines', 'importBatch');

        if ($preOrder->lines->isEmpty()) {
            return redirect()->route('pre-orders.show', $preOrder)->withErrors('Pré-commande vide, impossible de convertir.');
        }

        $defaultAddressId = null;
        $defaultContactId = null;

        if (! empty($data['companies_id'])) {
            $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $data['companies_id']]);
            $defaultContact = CompaniesContacts::getDefault(['companies_id' => $data['companies_id']]);

            if (is_null($defaultAddress) || is_null($defaultContact)) {
                return redirect()->route('pre-orders.show', $preOrder)
                    ->withInput()
                    ->withErrors("Le client sélectionné ne possède pas d'adresse et/ou de contact par défaut.");
            }

            $defaultAddressId = $defaultAddress->id;
            $defaultContactId = $defaultContact->id;
        }

        DB::transaction(function () use ($preOrder, $data, $defaultAddressId, $defaultContactId) {
            $order = Orders::create([
                'uuid' => Str::uuid(),
                'code' => $data['code'],
                'label' => $data['label'],
                'customer_reference' => $data['customer_reference'] ?? null,
                'companies_id' => $data['companies_id'] ?? null,
                'companies_contacts_id' => $defaultContactId,
                'companies_addresses_id' => $defaultAddressId,
                'validity_date' => $data['validity_date'] ?? null,
                'statu' => 1,
                'user_id' => $data['user_id'],
                'accounting_payment_conditions_id' => $data['accounting_payment_conditions_id'] ?? null,
                'accounting_payment_methods_id' => $data['accounting_payment_methods_id'] ?? null,
                'accounting_deliveries_id' => $data['accounting_deliveries_id'] ?? null,
                'comment' => $data['comment'] ?? null,
                'type' => $data['type'],
                'csv_file_name' => $preOrder->importBatch?->file_name,
            ]);

            foreach ($preOrder->lines as $index => $line) {
                $matchedProduct = $line->linkedProduct;

                $orderLine = OrderLines::create([
                    'orders_id' => $order->id,
                    'ordre' => $index + 1,
                    'code' => $matchedProduct?->code ?: $line->reference,
                    'product_id' => $line->linked_product_id,
                    'label' => $matchedProduct?->label ?: ($line->product ?: $line->reference),
                    'qty' => max((int) $line->quantity, 1),
                    'delivered_remaining_qty' => max((int) $line->quantity, 1),
                    'invoiced_remaining_qty' => max((int) $line->quantity, 1),
                    'methods_units_id' => $data['methods_units_id'],
                    'selling_price' => $line->matching_unit_price ?? $line->unit_price,
                    'discount' => $data['discount'] ?? 0,
                    'accounting_vats_id' => $data['accounting_vats_id'],
                    'delivery_date' => $data['delivery_date'] ?? null,
                ]);

                OrderLineDetails::create([
                    'order_lines_id' => $orderLine->id,
                ]);
            }

            $preOrder->update([
                'status' => PreOrder::STATUS_CONVERTED,
                'converted_order_id' => $order->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('pre-orders.show', $preOrder)->with('success', 'Pré-commande convertie en commande.');
    }

    public function exportZeroPriceCsv(PreOrder $preOrder): StreamedResponse
    {
        $preOrder->load('lines.linkedProduct', 'lines.suggestedProduct');

        $lines = $preOrder->lines->filter(
            fn ($line) => (float) ($line->unit_price ?? 0) == 0
        );

        $filename = 'articles_0eur_precommande_' . $preOrder->id . '.csv';

        return response()->streamDownload(function () use ($lines) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['article', 'nom', 'qté', 'nuance', 'épaisseur'], ';');

            foreach ($lines as $line) {
                $product = $line->linkedProduct ?? $line->suggestedProduct;
                fputcsv($handle, [
                    $line->reference ?? '',
                    $line->product ?? '',
                    $line->quantity,
                    $product?->material ?? '',
                    $product?->thickness ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(PreOrder $preOrder)
    {
        if ($preOrder->status !== PreOrder::STATUS_PENDING) {
            return redirect()->route('pre-orders.index')->withErrors('Seules les pré-commandes "À traiter" peuvent être supprimées.');
        }

        $preOrder->delete();

        return redirect()->route('pre-orders.index')->with('success', 'Pré-commande supprimée.');
    }

    private function generateOrderCodeByType(int $type): string
    {
        $lastOrderId = Orders::query()->max('id') ?? 0;
        $documentType = $type === 2 ? 'internal-order' : 'order';

        return $this->documentCodeGenerator->generateDocumentCode($documentType, (int) $lastOrderId);
    }

    private function resolveSourcePdfPath(PreOrder $preOrder): ?string
    {
        $sourcePdfName = basename(trim((string) $preOrder->source_pdf));

        if ($sourcePdfName === '' || ! Str::of($sourcePdfName)->lower()->endsWith('.pdf')) {
            return null;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $paths = [
            trim((string) config('pre_orders.processed_path', 'public/output/processed'), '/'),
            $this->resolveInputPath((string) config('pre_orders.input_path', 'pre-orders/input')),
            trim((string) config('pre_orders.output_path', 'output'), '/'),
            trim((string) config('pre_orders.done_path', 'output/done'), '/'),
        ];

        foreach ($paths as $path) {
            $candidate = trim($path . '/' . $sourcePdfName, '/');

            if ($disk->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
