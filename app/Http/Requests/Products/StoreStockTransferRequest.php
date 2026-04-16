<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'source_stock_location_products_id' => 'required|integer|exists:stock_location_products,id',
            'destination_stock_locations_id'    => 'required|integer|exists:stock_locations,id',
            'qty'                               => 'required|numeric|min:0.001',
            'tracability'                       => 'nullable|string|max:255',
            'user_id'                           => 'required|integer|exists:users,id',
        ];
    }
}
