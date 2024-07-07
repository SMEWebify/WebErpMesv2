<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Models\Companies\SupplierRating;

class SupplierRatingController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'purchases_id' => 'required|exists:purchases,id',
            'companies_id' => 'required|exists:companies,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        SupplierRating::create($request->all());

        return redirect()->back()->with('success', 'Rate saved successfully');
    }
}
