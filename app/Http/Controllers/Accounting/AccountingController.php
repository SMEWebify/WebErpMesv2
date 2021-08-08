<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\acVat;

class AccountingController extends Controller
{
    public function index()
    {

        $VatList = acVat::All();

        return view('accounting/accounting-index', [
             'VatList' => $VatList,
        ]);
    }
}
