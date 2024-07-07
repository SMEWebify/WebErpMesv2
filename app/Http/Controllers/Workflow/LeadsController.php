<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        return view('workflow/leads-index');
    }
}
