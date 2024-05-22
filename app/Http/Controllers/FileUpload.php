<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Http\Requests\StoreFileRequest;

class FileUpload extends Controller
{
    public function fileUpload(StoreFileRequest $request){

        $fileName = auth()->id() . '_' . time() . '.'. $request->file->extension();  
        $oringalFileName = $request->file->getClientOriginalName();
        $type = $request->file->getClientMimeType();
        $size = $request->file->getSize();

        $request->file->move(public_path('file'), $fileName);

        File::create([
            'user_id' => auth()->id(),
            'name' => $fileName,
            'original_file_name' => $oringalFileName,
            'type' => $type,
            'size' => $size,
            'companies_id' => $request->companie_id,
            'opportunities_id' => $request->opportunities_id,
            'quotes_id' => $request->quotes_id,
            'orders_id' => $request->order_id,
            'deliverys_id' => $request->delivery_id,
            'invoices_id' => $request->invoice_id,
            'products_id' => $request->product_id,
            'purchases_id' => $request->purchase_id,
            'purchase_receipts_id' => $request->purchase_receipt_id,
            'quality_non_conformities_id' => $request->quality_non_conformitie_id,
            'stock_move_id' => $request->stock_move_id,
        ]);

        return back()->with('success','File has been uploaded.')->with('file', $fileName);
    }

    public function photoUpload(StoreFileRequest $request){

        $fileName = auth()->id() . '_' . time() . '.'. $request->file->extension();  
        $oringalFileName = $request->file->getClientOriginalName();
        $type = $request->file->getClientMimeType();
        $size = $request->file->getSize();

        $request->file->move(public_path('photo'), $fileName);

        File::create([
            'user_id' => auth()->id(),
            'name' => $fileName,
            'original_file_name' => $oringalFileName,
            'type' => $type,
            'size' => $size,
            'companies_id' => $request->companie_id,
            'opportunities_id' => $request->opportunities_id,
            'quotes_id' => $request->quotes_id,
            'orders_id' => $request->order_id,
            'deliverys_id' => $request->delivery_id,
            'invoices_id' => $request->invoice_id,
            'products_id' => $request->product_id,
            'purchases_id' => $request->purchase_id,
            'purchase_receipts_id' => $request->purchase_receipt_id,
            'quality_non_conformities_id' => $request->quality_non_conformitie_id,
            'stock_move_id' => $request->stock_move_id,
            'as_photo' => 1,
        ]);

        return back()->with('success','File has been uploaded.')->with('file', $fileName);
    }
}
