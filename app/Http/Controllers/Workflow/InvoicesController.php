<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Workflow\Invoices;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;

class InvoicesController extends Controller
{
    //
    public function index()
    {    
        return view('workflow/invoices-index');
    }

    public function request()
    {    
        return view('workflow/invoices-request');
    }

    public function show(Invoices $id)
    {
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = companiesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = companiesContacts::select('id', 'FIRST_NAME','NAME')->get();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }

        return view('workflow/invoices-show', [
            'Invoice' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
        ]);
    }

    public function print(Invoices $id)
    {
        $Factory = Factory::first();
        return view('workflow/orders-print', [
            'Delivery' => $id,
            'Factory' => $Factory,
        ]);
    }
    
    public function update(UpdateInvoiceRequest $request)
    {
        $Invoice = Invoices::find($request->id);
        $Invoice->LABEL=$request->LABEL;
        $Invoice->statu=$request->statu;
        $Invoice->companies_id=$request->companies_id;
        $Invoice->companies_contacts_id=$request->companies_contacts_id;
        $Invoice->companies_addresses_id=$request->companies_addresses_id;
        $Invoice->comment=$request->comment;
        $Invoice->save();

        return redirect()->route('Invoice.show', ['id' =>  $Invoice->id])->with('success', 'Successfully updated Invoice');
    }
}
