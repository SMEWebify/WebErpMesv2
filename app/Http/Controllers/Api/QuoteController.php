<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Workflow\Quotes;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;

class QuoteController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Workflow\Quotes  $quote
     * @return \App\Http\Resources\QuoteResource
     */
    public function show(Quotes $quote)
    {
        return new QuoteResource($quote);
    }
}
