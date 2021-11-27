<?php

namespace App\Http\Controllers\workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderLinesController extends Controller
{
    //
    public function index()
    {    
        return view('workflow/orders-lines-index');
    }
}
