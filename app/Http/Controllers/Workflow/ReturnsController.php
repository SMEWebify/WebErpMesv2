<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Returns;

class ReturnsController extends Controller
{
    public function index()
    {
        return view('workflow.returns-index');
    }

    public function show(Returns $return)
    {
        return view('workflow.returns-show', [
            'return' => $return,
        ]);
    }
}
