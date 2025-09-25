<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Products\CustomerPriceList;
use App\Models\Products\Products;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerPriceListController extends Controller
{
    /**
     * Store a newly created customer price list entry.
     */
    public function store(Request $request, Products $product)
    {
        $this->normalizeInputs($request);

        $validated = $this->validateData($request);
        $validated['products_id'] = $product->id;

        try {
            CustomerPriceList::create($validated);
        } catch (QueryException $exception) {
            if (Str::contains($exception->getMessage(), 'customer_price_lists_unique')) {
                return redirect()
                    ->route('products.show', ['id' => $product->id])
                    ->withErrors(['customer_price_list' => __('general_content.customer_price_list_unique_error_trans_key')], 'customerPriceList');
            }

            throw $exception;
        }

        return redirect()
            ->route('products.show', ['id' => $product->id])
            ->with('success', 'Successfully added customer price.');
    }

    /**
     * Update the specified customer price list entry.
     */
    public function update(Request $request, Products $product, CustomerPriceList $priceList)
    {
        if ((int) $priceList->products_id !== (int) $product->id) {
            abort(404);
        }

        $this->normalizeInputs($request);

        $validated = $this->validateData($request);

        try {
            $priceList->update($validated);
        } catch (QueryException $exception) {
            if (Str::contains($exception->getMessage(), 'customer_price_lists_unique')) {
                return redirect()
                    ->route('products.show', ['id' => $product->id])
                    ->withErrors(['customer_price_list' => __('general_content.customer_price_list_unique_error_trans_key')], 'customerPriceList');
            }

            throw $exception;
        }

        return redirect()
            ->route('products.show', ['id' => $product->id])
            ->with('success', 'Successfully updated customer price.');
    }

    /**
     * Remove the specified customer price list entry from storage.
     */
    public function destroy(Products $product, CustomerPriceList $priceList)
    {
        if ((int) $priceList->products_id !== (int) $product->id) {
            abort(404);
        }

        $priceList->delete();

        return redirect()
            ->route('products.show', ['id' => $product->id])
            ->with('success', 'Successfully deleted customer price.');
    }

    /**
     * Normalize nullable inputs before validation.
     */
    protected function normalizeInputs(Request $request): void
    {
        $request->merge([
            'companies_id' => $request->input('companies_id') !== '' ? $request->input('companies_id') : null,
            'customer_type' => $request->input('customer_type') !== '' ? $request->input('customer_type') : null,
            'max_qty' => $request->input('max_qty') !== '' ? $request->input('max_qty') : null,
        ]);
    }

    /**
     * Validate incoming data.
     */
    protected function validateData(Request $request): array
    {
        return $request->validateWithBag('customerPriceList', [
            'companies_id' => ['nullable', 'integer', 'exists:companies,id'],
            'customer_type' => ['nullable', 'integer', 'in:1,2'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'max_qty' => ['nullable', 'integer', 'gte:min_qty'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
