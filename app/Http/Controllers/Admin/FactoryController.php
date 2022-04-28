<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Factory;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingVat;
use App\Http\Requests\Admin\UpdateFactoryRequest;

class FactoryController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $VATSelect  =  AccountingVat::select('id', 'label')->orderBy('rate')->get();
        $Factory  =  Factory::firstOrCreate(
                                    ['id' =>'1',],
                                );
                        return view('admin/factory-index', [
                            'VATSelect' => $VATSelect,
                            'Factory' => $Factory,
                        ]);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update(UpdateFactoryRequest $request)
    {
        $Factory = Factory::first();
        $Factory->name = $request->name;
        $Factory->address = $request->address;
        $Factory->city = $request->city; 
        $Factory->zipcode = $request->zipcode;
        $Factory->region = $request->region;
        $Factory->country = $request->country;
        $Factory->phone_number = $request->phone_number; 
        $Factory->mail = $request->mail;
        $Factory->web_site = $request->web_site;
        $Factory->picture = $request->picture;
        $Factory->siren = $request->siren; 
        $Factory->nat_regis_num = $request->nat_regis_num;
        $Factory->vat_num = $request->vat_num;
        $Factory->accounting_vats_id = $request->accounting_vats_id;
        $Factory->share_capital = $request->share_capital; 
        $Factory->curency = $request->curency;
        $Factory->add_day_validity_quote = $request->add_day_validity_quote;
        $Factory->add_delivery_delay_order =  $request->add_delivery_delay_order;
        $Factory->task_barre_code =  $request->task_barre_code;

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/factory'), $filename);
            $Factory->picture =  $filename;
        }

        $Factory->save();

        return redirect()->route('admin.factory')->with('success', 'Successfully updated factory inforamations');
    }
}
