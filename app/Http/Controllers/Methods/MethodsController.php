<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;

class MethodsController extends Controller
{
    //
    public function index()
    {
        return view('methods/methods-index');
    }
}
