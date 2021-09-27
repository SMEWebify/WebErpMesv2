<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingVat;

class FactoryController extends Controller
{
    //
    public function index()
    {
        $VATSelect = AccountingVat::select('id', 'LABEL')->orderBy('RATE')->get();

        return view('admin/factory-index', [
            'VATSelect' => $VATSelect,
            
        ]);
    }
}
