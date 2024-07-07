<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Products\Products;
use Illuminate\Support\Facades\Log;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;

class SearchController extends Controller
{
/**
     * Show the navbar search results.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function showNavbarSearchResults(Request $request)
    {
        // Check that the search keyword is present.

        if (! $request->filled('searchVal')) {
            return back();
        }

        // Get the search keyword.

        $keyword = $request->input('searchVal');

        Log::info("A navbar search was triggered with next keyword => {$keyword}");

        $results = Search::add(Companies::class, ['code', 'label'])
                        ->add(Quotes::class, ['code', 'label'])
                        ->add(Orders::class, ['code', 'label'])
                        ->add(Deliverys::class, ['code', 'label'])
                        ->add(Invoices::class, ['code', 'label'])
                        ->add(Products::class, ['code', 'label'])
                        ->beginWithWildcard()
                        ->orderByModel([
                            Companies::class, Quotes::class, Orders::class, Deliverys::class, Invoices::class,Products::class,
                        ])
                        ->search($keyword);

        return view('search', compact('results', 'keyword'));
        
    }
}