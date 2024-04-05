<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SerialNumbersController extends Controller
{
    /**
     * @return view
     */
    public function index()
    {
        return view('products/serial-numbers-index');
    }
}
