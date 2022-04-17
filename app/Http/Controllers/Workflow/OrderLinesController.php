<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderLinesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {    
        return view('workflow/orders-lines-index');
    }
}
