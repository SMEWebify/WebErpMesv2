<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Services\Files\FileableRegistry;
use App\Services\Files\FileStorageService;
use App\Services\Files\FileRole;

/**
 * Blade form entry point kept for the "Documents" cards of the quote, order,
 * delivery, invoice, purchase and company pages.
 *
 * The React FileManager (FileApiController) is the modern path; this one now
 * delegates to the same storage service so both write to private storage and
 * populate kind / role identically.
 */
class FileUpload extends Controller
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fileUpload(StoreFileRequest $request)
    {
        return $this->handleFileUpload($request);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function photoUpload(StoreFileRequest $request)
    {
        return $this->handleFileUpload($request, asPhoto: true);
    }

    /**
     * Store the upload and attach it to whichever entity the form declared.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleFileUpload(StoreFileRequest $request, bool $asPhoto = false)
    {
        $file = $this->storage->store($request->file('file'), [
            'comment' => $request->input('comment'),
            'hashtags' => $this->storage->normalizeHashtags($request->input('hashtags')),
            'as_photo' => $asPhoto,
        ]);

        // The Blade forms still post the historical {entity}_id hidden inputs.
        $legacyInputs = [
            'companies_id' => 'company',
            'opportunities_id' => 'opportunity',
            'quotes_id' => 'quote',
            'orders_id' => 'order',
            'deliverys_id' => 'delivery',
            'invoices_id' => 'invoice',
            'products_id' => 'product',
            'purchases_id' => 'purchase',
            'purchase_receipts_id' => 'purchase-receipt',
            'quality_non_conformities_id' => 'non-conformity',
            'stock_move_id' => 'stock-move',
        ];

        foreach ($legacyInputs as $input => $alias) {
            if (! $request->filled($input)) {
                continue;
            }

            $entity = FileableRegistry::find($alias, $request->input($input));

            if ($entity !== null) {
                $this->storage->attach($file, $entity, $asPhoto ? FileRole::PHOTO : null);
            }
        }

        return back()
            ->with('success', __('general_content.file_uploaded_success_trans_key'))
            ->with('file', $file->name);
    }
}
