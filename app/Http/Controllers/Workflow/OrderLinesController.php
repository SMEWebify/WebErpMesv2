<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Services\ImportCsvService;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OrderLineDetails;
use App\Http\Requests\Workflow\UpdateOrderLineDetailsRequest;

class OrderLinesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        return view('workflow/orders-lines-index');
    }

    /**
     * @param \App\Http\Requests\UpdateOrderLineDetailsRequest $request
     * @param int $idOrder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($idOrder, UpdateOrderLineDetailsRequest $request)
    {
        $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
        $OrderLineDetails->update($request->validated());

        return redirect()->route('orders.show', ['id' => $idOrder])->with('success', 'Successfully updated order detail line');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage($idOrder,Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/order-lines'), $filename);
            $OrderLineDetails->update(['picture' => $filename]);
            $OrderLineDetails->save();
            return redirect()->route('orders.show', ['id' =>  $idOrder])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    public function import($idOrder, Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importOrderLines($idOrder, $request);
        return redirect()->back();
    }
}