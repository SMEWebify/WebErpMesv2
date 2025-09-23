<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Products\StockMove;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Opportunities;
use App\Http\Requests\StoreFileRequest;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Quality\QualityNonConformity;

class FileUpload extends Controller
{
/**
     * @param \App\Http\Requests\StoreFileRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fileUpload(StoreFileRequest $request)
    {
        return $this->handleFileUpload($request, 'file');
    }

    /**
     * @param \App\Http\Requests\StoreFileRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function photoUpload(StoreFileRequest $request)
    {
        return $this->handleFileUpload($request, 'photo', true);
    }

    /**
     * Handle the file upload logic.
     *
     * @param \App\Http\Requests\StoreFileRequest $request
     * @param string $directory
     * @param bool $asPhoto
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleFileUpload(StoreFileRequest $request, string $directory, bool $asPhoto = false)
    {
        $fileName = Auth::id() . '_' . time() . '.' . $request->file->extension();
        $originalFileName = $request->file->getClientOriginalName();
        $type = $request->file->getClientMimeType();
        $size = $request->file->getSize();
        $comment = $request->input('comment');
        $hashtags = $this->normalizeHashtags($request->input('hashtags'));

        $request->file->move(public_path($directory), $fileName);

        $fileData = [
            'user_id' => Auth::id(),
            'name' => $fileName,
            'original_file_name' => $originalFileName,
            'type' => $type,
            'size' => $size,
        ];

        if ($comment !== null) {
            $trimmedComment = trim($comment);
            $fileData['comment'] = $trimmedComment === '' ? null : $trimmedComment;
        }

        if (!empty($hashtags)) {
            $fileData['hashtags'] = $hashtags;
        }

        if ($asPhoto) {
            $fileData['as_photo'] = 1;
        }

        $file = File::create($fileData);

        $associations = [
            'companies_id' => Companies::class,
            'opportunities_id' => Opportunities::class,
            'quotes_id' => Quotes::class,
            'orders_id' => Orders::class,
            'deliverys_id' => Deliverys::class,
            'invoices_id' => Invoices::class,
            'products_id' => Products::class,
            'purchases_id' => Purchases::class,
            'purchase_receipts_id' => PurchaseReceipt::class,
            'quality_non_conformities_id' => QualityNonConformity::class,
            'stock_move_id' => StockMove::class,
        ];

        foreach ($associations as $key => $model) {
            if ($request->filled($key)) {
                $entity = $model::find($request->$key);
                if ($entity) {
                    $entity->files()->save($file);
                }
            }
        }

        return back()->with('success', 'File has been uploaded.')->with('file', $fileName);
    }

    /**
     * Normalize a raw hashtags string into an array of unique tags.
     *
     * @param string|null $rawHashtags
     * @return array<int, string>
     */
    private function normalizeHashtags(?string $rawHashtags): array
    {
        if ($rawHashtags === null) {
            return [];
        }

        $parts = preg_split('/[\s,]+/u', $rawHashtags);

        if ($parts === false) {
            return [];
        }

        $hashtags = [];

        foreach ($parts as $part) {
            $tag = trim($part);

            if ($tag === '') {
                continue;
            }

            $tag = trim(ltrim($tag, "#＃"));

            if ($tag === '') {
                continue;
            }

            $tag = mb_substr($tag, 0, 50);
            $normalizedKey = mb_strtolower($tag);

            if (!array_key_exists($normalizedKey, $hashtags)) {
                $hashtags[$normalizedKey] = $tag;
            }
        }

        return array_values($hashtags);
    }
}
