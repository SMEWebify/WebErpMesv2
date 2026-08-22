<?php

namespace App\Http\Requests\Products;

use App\Models\Products\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => 'nullable|string|max:255',
            'code'       => 'nullable|string|max:100|unique:inventories,code',
            'scope_type' => [
                'required',
                Rule::in([
                    Inventory::SCOPE_ALL,
                    Inventory::SCOPE_LOCATION,
                    Inventory::SCOPE_CATEGORY,
                ]),
            ],
            // Required only when the scope is not "all".
            'scope_ids'   => 'array|required_unless:scope_type,' . Inventory::SCOPE_ALL,
            'scope_ids.*' => 'integer|min:1',
        ];
    }
}
