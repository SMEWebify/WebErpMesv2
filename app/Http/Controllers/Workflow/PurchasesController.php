<?php

namespace App\Http\Controllers\workflow;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;

class PurchasesController extends Controller
{
    //
    public function index()
    {    
        return view('workflow/purchases-index');
    }

    public function request()
    {    
        return view('workflow/purchases-request');
    }

    public function quotation()
    {    
        return view('workflow/purchases-quotation');
    }

    public function reciept()
    {    
        return view('workflow/purchases-reciept');
    }

    public function invoice()
    {    
        return view('workflow/purchases-invoice');
    }
}
