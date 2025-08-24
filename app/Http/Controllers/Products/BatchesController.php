<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Products\Batch;

class BatchesController extends Controller
{
    public function index()
    {
        $batches = Batch::with('product')->paginate(50);
        return view('products.batches-index', [
            'batcheslist' => $batches,
        ]);
    }
}
