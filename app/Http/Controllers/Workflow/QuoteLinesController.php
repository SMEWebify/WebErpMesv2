<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Workflow\QuoteLineDetails;
use App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest;

class QuoteLinesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {    
        return view('workflow/quotes-lines-index');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update($idQuote, UpdateQuoteLineDetailsRequest $request)
    {
        
        $QuoteLineDetails = QuoteLineDetails::find($request->id);
        $QuoteLineDetails->x_size=$request->x_size;
        $QuoteLineDetails->y_size=$request->y_size;
        $QuoteLineDetails->z_size=$request->z_size;
        $QuoteLineDetails->x_oversize=$request->x_oversize;
        $QuoteLineDetails->y_oversize=$request->y_oversize;
        $QuoteLineDetails->z_oversize=$request->z_oversize;
        $QuoteLineDetails->diameter=$request->diameter;
        $QuoteLineDetails->diameter_oversize=$request->diameter_oversize;
        $QuoteLineDetails->material=$request->material;
        $QuoteLineDetails->thickness=$request->thickness;
        $QuoteLineDetails->weight=$request->weight;
        $QuoteLineDetails->material_loss_rate=$request->material_loss_rate;
        $QuoteLineDetails->save();
        return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully updated quote detail line');
    }
    
    /**
     * @param Request $request
     * @return View
     */
    public function StoreImage($idQuote,Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/quote-lines'), $filename);
            $QuoteLineDetails->update(['picture' => $filename]);
            $QuoteLineDetails->save();
            return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}