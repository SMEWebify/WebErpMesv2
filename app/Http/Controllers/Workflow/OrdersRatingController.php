<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Models\Workflow\OrderRating;

class OrdersRatingController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'orders_id' => 'required|exists:orders,id',
            'companies_id' => 'required|exists:companies,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        OrderRating::create($request->all());

        return redirect()->back()->with('success', 'Rate saved successfully');
    }
}
