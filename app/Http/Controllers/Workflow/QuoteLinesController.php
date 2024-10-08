<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Services\ImportCsvService;
use App\Http\Controllers\Controller;
use App\Models\Workflow\QuoteLineDetails;
use App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest;

class QuoteLinesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        return view('workflow/quotes-lines-index');
    }

    /**
     * @param \App\Http\Requests\UpdateQuoteLineDetailsRequest $request
     * @param int $idQuote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($idQuote, UpdateQuoteLineDetailsRequest $request)
    {
        $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
        $QuoteLineDetails->update($request->validated());

        return redirect()->route('quotes.show', ['id' => $idQuote])->with('success', 'Successfully updated quote detail line');
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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

    public function import($idQuote, Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importQuoteLines($idQuote, $request);
        return redirect()->back();
    }
}