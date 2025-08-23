<?php

namespace App\Http\Controllers;

use App\Models\Products\SerialNumbers;

class ProductionTraceController extends Controller
{
    public function show(SerialNumbers $serialNumber)
    {
        $serialNumber->load('components.componentSerial');
        return view('production.trace-show', ['serialNumber' => $serialNumber]);
    }
}
