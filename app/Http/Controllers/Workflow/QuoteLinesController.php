<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Models\Workflow\QuoteLines;
use App\Http\Controllers\Controller;

class QuoteLinesController extends Controller
{
    //
    public function index()
    {    
        return view('workflow/quotes-lines-index');
    }
}
