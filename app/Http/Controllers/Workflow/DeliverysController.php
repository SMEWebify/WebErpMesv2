<?php

namespace App\Http\Controllers\workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliverysController extends Controller
{
       //
        public function index()
        {    
            return view('workflow/deliverys-index');
        }

        public function request()
        {    
            return view('workflow/deliverys-request');
        }

}
