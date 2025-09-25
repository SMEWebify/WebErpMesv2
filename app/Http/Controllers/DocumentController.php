<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Contracts\View\View;

class DocumentController extends Controller
{
    /**
     * Display a listing of the stored documents in a consolidated GED view.
     */
    public function index(): View
    {
        $files = File::with([
            'UserManagement:id,name',
            'companies:id,label',
            'opportunities:id,label',
            'quotes:id,code',
            'orders:id,code',
            'deliverys:id,code',
            'invoices:id,code',
            'products:id,label',
            'purchaseReceipt:id,code',
            'stockMove:id',
            'qualityNonConformity:id,code',
        ])->latest()->get();

        $documents = $files->map(function (File $file) {
            $createdAt = $file->created_at;
            $updatedAt = $file->updated_at;

            return [
                'id' => $file->id,
                'name' => $file->name ?? $file->original_file_name,
                'original_file_name' => $file->original_file_name,
                'type' => $file->type,
                'size' => $file->size,
                'formatted_size' => $file->formatted_size,
                'comment' => $file->comment,
                'hashtags' => $file->hashtags ?? [],
                'uploaded_by' => optional($file->UserManagement)->name,
                'created_at' => $createdAt?->toIso8601String(),
                'created_at_human' => $createdAt
                    ? $createdAt->copy()->timezone(config('app.timezone'))
                        ->locale(app()->getLocale())->translatedFormat('d F Y')
                    : null,
                'updated_at' => $updatedAt?->toIso8601String(),
                'updated_at_human' => $updatedAt
                    ? $updatedAt->copy()->timezone(config('app.timezone'))
                        ->locale(app()->getLocale())->translatedFormat('d F Y')
                    : null,
                'linked_entities' => $this->formatLinkedEntities($file),
            ];
        });

        $translations = [
            'name' => __('general_content.name_trans_key'),
            'type' => __('general_content.document_type_trans_key'),
            'size' => __('general_content.file_size_trans_key'),
            'uploaded_at' => __('general_content.uploaded_at_trans_key'),
            'updated_at' => __('general_content.updated_at_trans_key'),
            'hashtags' => __('general_content.hashtags_trans_key'),
            'uploaded_by' => __('general_content.uploaded_by_trans_key'),
            'linked_entities' => __('general_content.related_entities_trans_key'),
            'filters' => [
                'typeLabel' => __('general_content.document_type_trans_key'),
                'typePlaceholder' => __('general_content.select_type_trans_key'),
                'searchPlaceholder' => __('general_content.document_search_placeholder_trans_key'),
                'hashtagPlaceholder' => __('general_content.hashtag_filter_placeholder_trans_key'),
                'dateFrom' => __('general_content.start_date_trans_key'),
                'dateTo' => __('general_content.end_date_trans_key'),
                'reset' => __('general_content.reset_filters_trans_key'),
                'noResult' => __('general_content.no_documents_found_trans_key'),
                'uploaderLabel' => __('general_content.uploaded_by_trans_key'),
            ],
        ];

        return view('documents.index', [
            'documents' => $documents,
            'translations' => $translations,
        ]);
    }

    /**
     * Build a human readable list of linked entities for a given file.
     */
    private function formatLinkedEntities(File $file): array
    {
        $mapped = [
            __('general_content.companies_trans_key') => $file->companies->pluck('label')->filter()->all(),
            __('general_content.opportunities_trans_key') => $file->opportunities->pluck('label')->filter()->all(),
            __('general_content.quote_trans_key') => $file->quotes->pluck('code')->filter()->all(),
            __('general_content.orders_trans_key') => $file->orders->pluck('code')->filter()->all(),
            __('general_content.delivery_notes_trans_key') => $file->deliverys->pluck('code')->filter()->all(),
            __('general_content.invoice_trans_key') => $file->invoices->pluck('code')->filter()->all(),
            __('general_content.product_trans_key') => $file->products->pluck('label')->filter()->all(),
            __('general_content.purchase_receipt_trans_key') => $file->purchaseReceipt->pluck('code')->filter()->all(),
            __('general_content.stock_trans_key') => $file->stockMove->pluck('code')->filter()->all(),
            __('general_content.non_conformitie_trans_key') => $file->qualityNonConformity->pluck('code')->filter()->all(),
        ];

        return collect($mapped)
            ->filter(fn (array $items) => count($items) > 0)
            ->map(fn (array $items, string $label) => $label . ': ' . implode(', ', $items))
            ->values()
            ->all();
    }
}
