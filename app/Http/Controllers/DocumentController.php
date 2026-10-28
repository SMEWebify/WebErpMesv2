<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Files\FileConfidentiality;
use App\Services\Files\FileTranslations;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    /**
     * Display a listing of the stored documents in a consolidated GED view.
     */
    public function index(): View
    {
        // Employee folder documents (contract, payslip, expense receipt) stay
        // out of the shared listing: their name alone is personal data.
        $hiddenIds = FileConfidentiality::hiddenFileIds(Auth::user());
        $canSeeProducts = Auth::user()->can('products-menu');

        $files = File::with([
            'UserManagement:id,name',
            'companies:id,label',
            'opportunities:id,label',
            'quotes:id,code',
            'quoteLines:id,quotes_id,code,label',
            'orders:id,code',
            'orderLines:id,orders_id,code,label',
            'deliverys:id,code',
            'invoices:id,code',
            'products:id,label',
            'purchaseReceipt:id,code',
            'stockMove:id',
            'qualityNonConformity:id,code',
        ])
            ->when($hiddenIds !== [], fn ($query) => $query->whereNotIn('id', $hiddenIds))
            ->latest()
            ->get();

        $documents = $files->map(function (File $file) use ($canSeeProducts) {
            $createdAt = $file->created_at;
            $updatedAt = $file->updated_at;

            return [
                'id' => $file->id,
                'name' => $file->name ?? $file->original_file_name,
                'original_file_name' => $file->original_file_name,
                'type' => $file->type,
                'kind' => $file->kind,
                'extension' => $file->extension,
                'icon' => $file->icon,
                'download_url' => $file->download_url,
                'view_url' => $file->view_url,
                'is_viewable' => $file->is_viewable,
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
                'linked_entities' => $this->formatLinkedEntities($file, $canSeeProducts),
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
            'actions' => __('general_content.action_trans_key'),
            'preview' => __('general_content.preview_trans_key'),
            'close' => __('general_content.close_trans_key'),
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
            // Strings of the shared file viewer (FileViewer and its engines).
            'viewer' => FileTranslations::all(),
        ];

        return view('documents.index', [
            'documents' => $documents,
            'translations' => $translations,
        ]);
    }

    /**
     * Entities a file is attached to, each with a link to its screen when
     * there is one: [{type, label, url}].
     */
    private function formatLinkedEntities(File $file, bool $canSeeProducts): array
    {
        $lineLabel = fn ($line) => trim(($line->code ?? '') . ' ' . ($line->label ?? ''));

        $entities = [];
        $push = function (string $type, $items, callable $label, ?callable $url) use (&$entities) {
            foreach ($items as $item) {
                $entities[] = [
                    'type' => $type,
                    'label' => (string) ($label($item) ?: '#' . $item->id),
                    'url' => $url ? $url($item) : null,
                ];
            }
        };

        $push(__('general_content.companies_trans_key'), $file->companies, fn ($c) => $c->label,
            fn ($c) => route('companies.show', ['id' => $c->id]));
        $push(__('general_content.opportunities_trans_key'), $file->opportunities, fn ($o) => $o->label,
            fn ($o) => route('opportunities.show', ['id' => $o->id]));
        $push(__('general_content.quote_trans_key'), $file->quotes, fn ($q) => $q->code,
            fn ($q) => route('quotes.show', ['id' => $q->id]));
        $push(__('general_content.quote_lines_related_trans_key'), $file->quoteLines, $lineLabel,
            fn ($l) => $l->quotes_id ? route('quotes.show', ['id' => $l->quotes_id]) : null);
        $push(__('general_content.orders_trans_key'), $file->orders, fn ($o) => $o->code,
            fn ($o) => route('orders.show', ['id' => $o->id]));
        $push(__('general_content.order_lines_related_trans_key'), $file->orderLines, $lineLabel,
            fn ($l) => $l->orders_id ? route('orders.show', ['id' => $l->orders_id]) : null);
        $push(__('general_content.delivery_notes_trans_key'), $file->deliverys, fn ($d) => $d->code,
            fn ($d) => route('deliverys.show', ['id' => $d->id]));
        $push(__('general_content.invoice_trans_key'), $file->invoices, fn ($i) => $i->code,
            fn ($i) => route('invoices.show', ['id' => $i->id]));
        $push(__('general_content.product_trans_key'), $file->products, fn ($p) => $p->label,
            $canSeeProducts ? fn ($p) => route('products.show', ['id' => $p->id]) : null);
        $push(__('general_content.purchase_receipt_trans_key'), $file->purchaseReceipt, fn ($r) => $r->code,
            fn ($r) => route('purchase.receipts.show', ['id' => $r->id]));
        $push(__('general_content.stock_trans_key'), $file->stockMove, fn ($m) => null, null);
        $push(__('general_content.non_conformitie_trans_key'), $file->qualityNonConformity, fn ($n) => $n->code,
            fn () => route('quality.nonConformitie'));

        return $entities;
    }
}
