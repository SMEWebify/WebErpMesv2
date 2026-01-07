<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'label' => 'required|string|max:255',
            'ind' => 'nullable|string',
            'methods_services_id' => 'required|integer',
            'methods_families_id' => 'required|integer',
            'purchased_price' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
            'methods_units_id' => 'required|integer',
            'material' => 'nullable|string|max:255',
            'thickness' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'bend_count' => 'nullable|integer|min:0',
            'x_size' => 'nullable|numeric',
            'y_size' => 'nullable|numeric',
            'z_size' => 'nullable|numeric',
            'x_oversize' => 'nullable|numeric',
            'y_oversize' => 'nullable|numeric',
            'z_oversize' => 'nullable|numeric',
            'comment' => 'nullable|string',
            'tracability_type' => 'nullable|numeric|max:255',
            'qty_eco_min' => 'nullable|numeric',
            'qty_eco_max' => 'nullable|numeric',
            'diameter' => 'nullable|numeric',
            'diameter_oversize' => 'nullable|numeric',
            'section_size' => 'nullable|numeric',
            'finishing' => 'nullable|string|max:255',
            'cad_file_path' => 'nullable|string|max:255',
            'cam_file_path' => 'nullable|string|max:255',
            'picture'=> 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
