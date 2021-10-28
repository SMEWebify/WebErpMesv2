<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Factory;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingVat;
use App\Http\Requests\Admin\UpdateFactoryRequest;

class FactoryController extends Controller
{
    //
    public function index()
    {
        $VATSelect  =  AccountingVat::select('id', 'LABEL')->orderBy('RATE')->get();
        $Factory  =  Factory::firstOrCreate(
                                    ['id' =>'1',],
                                );

                        return view('admin/factory-index', [
                            'VATSelect' => $VATSelect,
                            'Factory' => $Factory,
                        ]);
    }


    public function update(UpdateFactoryRequest $request)
    {

            $Factory = Factory::first();
                        $Factory->NAME = $request->NAME;
                        $Factory->ADDRESS = $request->ADDRESS;
                        $Factory->CITY = $request->CITY; 
                        $Factory->ZIPCODE = $request->ZIPCODE;
                        $Factory->REGION = $request->REGION;
                        $Factory->COUNTRY = $request->COUNTRY;
                        $Factory->PHONE_NUMBER = $request->PHONE_NUMBER; 
                        $Factory->MAIL = $request->MAIL;
                        $Factory->WEB_SITE = $request->WEB_SITE;
                        $Factory->PICTURE = $request->PICTURE;
                        $Factory->SIREN = $request->SIREN; 
                        $Factory->nat_regis_num = $request->nat_regis_num;
                        $Factory->vat_num = $request->vat_num;
                        $Factory->accounting_vats_id = $request->accounting_vats_id;
                        $Factory->share_capital = $request->share_capital; 
                        $Factory->curency = $request->curency;
                        $Factory->add_day_validity_quote = $request->add_day_validity_quote;
                        $Factory->add_delivery_delay_order =  $request->add_delivery_delay_order;
                        $Factory->save();

        return redirect()->route('admin.factory')->with('success', 'Successfully updated factory inforamations');
    }
}
