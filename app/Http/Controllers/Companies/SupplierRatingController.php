<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Models\Companies\SupplierRating;

class SupplierRatingController extends Controller
{
    /**
     * Store a newly created resource in storage.
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
