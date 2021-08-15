<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVat;

class AccountingController extends Controller
{
    public function index()
    {

        $VatList = AccountingVat::All();

        return view('accounting/accounting-index', [
             'VatList' => $VatList,
        ]);
    }
}
