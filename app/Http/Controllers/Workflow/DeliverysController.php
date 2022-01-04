<?php

namespace App\Http\Controllers\workflow;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;

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

    public function show(Deliverys $id)
    {
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = companiesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = companiesContacts::select('id', 'FIRST_NAME','NAME')->get();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('danger', 'Please check factory information');
        }

        return view('workflow/deliverys-show', [
            'Delivery' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
        ]);
    }

    public function print(Deliverys $id)
    {
        $Factory = Factory::first();
        return view('workflow/orders-print', [
            'Delivery' => $id,
            'Factory' => $Factory,
        ]);
    }
    
    public function update(UpdateDeliveryRequest $request)
    {
        $Delivery = Deliverys::find($request->id);
        $Delivery->LABEL=$request->LABEL;
        $Delivery->statu=$request->statu;
        $Delivery->companies_id=$request->companies_id;
        $Delivery->companies_contacts_id=$request->companies_contacts_id;
        $Delivery->companies_addresses_id=$request->companies_addresses_id;
        $Delivery->comment=$request->comment;
        $Delivery->save();

        return redirect()->route('delivery.show', ['id' =>  $Delivery->id])->with('success', 'Successfully updated Delivery');
    }

}
