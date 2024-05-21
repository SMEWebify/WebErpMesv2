<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        return view('workshop/workshop');
    }

    /**
     * @return View
     */
    public function taskLines()
    {
        return view('workshop/workshop-task-lines');
    }

    public function statu(Request $request)
    {
        return view('workshop/workshop-task-statu', ['TaskId' => $request->id]);
    }

    public function stockDetail(Request $request)
    {
        return view('workshop/workshop-stock-detail', ['StockDetailId' => $request->id]);
    }
    
}
