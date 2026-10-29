<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Services\Purchases\PurchaseCriticality;
use App\Services\Purchases\ReceiptDocumentCheck;
use Illuminate\Http\Request;

/**
 * Liste qualité des lignes de réception en attente de visa documentaire. Lecture pour
 * tout le module achats ; le visa lui-même exige reception-document-check
 * (PurchasesReceiptController::updateLineDocumentCheck).
 */
class ReceiptDocumentCheckController extends Controller
{
    public const FILTERS = ['awaiting', 'non_compliant', 'compliant', 'all'];

    public function index(Request $request)
    {
        return view('purchases/purchases-receipt-document-check', [
            'documentCheck' => self::frontConfig($request),
            'endpoints'     => [
                'list'  => route('purchases.receipt.document-check.json'),
                'check' => route('purchase.receipts.lines.document-check', ['purchaseReceiptLine' => '__ID__']),
            ],
        ]);
    }

    public function listJson(Request $request)
    {
        $filter = in_array($request->input('filter'), self::FILTERS, true) ? $request->input('filter') : 'awaiting';
        $search = trim((string) $request->input('search', ''));

        $query = $filter === 'awaiting'
            ? ReceiptDocumentCheck::awaitingQuery()
            : PurchaseReceiptLines::query()
                ->join('purchase_lines', 'purchase_lines.id', '=', 'purchase_receipt_lines.purchase_line_id')
                ->select('purchase_receipt_lines.*')
                ->when($filter === 'all', fn ($q) => $q->whereNotNull('purchase_receipt_lines.document_check_result'))
                ->when($filter !== 'all', fn ($q) => $q->where('purchase_receipt_lines.document_check_result', $filter));

        $query->join('purchase_receipts', 'purchase_receipts.id', '=', 'purchase_receipt_lines.purchase_receipt_id')
            ->whereNull('purchase_receipts.deleted_at')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
                $q->where(function ($w) use ($like) {
                    $w->whereRaw("purchase_receipts.code LIKE ? ESCAPE '!'", [$like])
                      ->orWhereRaw("purchase_lines.label LIKE ? ESCAPE '!'", [$like])
                      ->orWhereRaw("purchase_lines.code LIKE ? ESCAPE '!'", [$like]);
                });
            })
            ->orderBy('purchase_receipt_lines.created_at', $filter === 'awaiting' ? 'asc' : 'desc')
            ->orderBy('purchase_receipt_lines.id');

        $items = $query->with([
            'purchaseReceipt.companie:id,code,label',
            'purchaseLines.purchase:id,code',
            'documentChecker:id,name',
            'qualityNonConformity:id,code',
        ])->paginate(25);

        return response()->json([
            'data' => collect($items->items())->map(fn (PurchaseReceiptLines $line) => self::row($line))->values(),
            'meta' => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ],
        ]);
    }

    private static function row(PurchaseReceiptLines $line): array
    {
        $receipt      = $line->purchaseReceipt;
        $purchaseLine = $line->purchaseLines;

        return [
            'id'                        => $line->id,
            'receipt_id'                => $receipt?->id,
            'receipt_code'              => $receipt?->code,
            'receipt_url'               => $receipt ? route('purchase.receipts.show', ['id' => $receipt->id]) : null,
            'received_at'               => $line->created_at?->format('Y-m-d'),
            'supplier_label'            => $receipt?->companie?->label,
            'purchase_code'             => $purchaseLine?->purchase?->code,
            'purchase_url'              => $purchaseLine?->purchases_id ? route('purchases.show', ['id' => $purchaseLine->purchases_id]) : null,
            'purchase_line_label'       => $purchaseLine?->label,
            'receipt_qty'               => $line->receipt_qty,
            'required_certificate_type' => ReceiptDocumentCheck::requiredCertificate($line),
            'received_certificate_type' => $line->received_certificate_type,
            'certificate_gap'           => ReceiptDocumentCheck::gapFor($line),
            'document_check_status'     => ReceiptDocumentCheck::documentStatus($line),
            'document_check_result'     => $line->document_check_result,
            'document_check_comment'    => $line->document_check_comment,
            'document_check_at'         => $line->document_check_at?->format('Y-m-d H:i'),
            'document_checker_name'     => $line->documentChecker?->name,
            'physical_status'           => ReceiptDocumentCheck::physicalStatus($line),
            'final_decision'            => ReceiptDocumentCheck::decision($line),
            'quality_non_conformity_id' => $line->quality_non_conformity_id,
            'nc_code'                   => $line->qualityNonConformity?->code,
        ];
    }

    /** Droits, valeurs de certificat et textes du tiroir de visa (fiche réception et liste). */
    public static function frontConfig(Request $request): array
    {
        return [
            'canCheck'         => (bool) $request->user()?->can('reception-document-check'),
            'certificateTypes' => PurchaseCriticality::options()['certificate_type'],
            'texts'            => __('receipt_document_check'),
        ];
    }
}
