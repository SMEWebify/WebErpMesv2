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
            'label'=>'required',
            'methods_services_id'=>'required',
            'methods_families_id'=>'required',
            'methods_units_id'=>'required',
            'sold'=>'required',
            'purchased'=>'required',
            'tracability_type'=>'required',
            'picture'=> 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
