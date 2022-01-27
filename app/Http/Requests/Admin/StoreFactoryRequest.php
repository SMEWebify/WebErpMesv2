<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFactoryRequest extends FormRequest
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
            //
            'name' =>'required', 
            'ADDRESS' =>'required',
            'city' =>'required',
            'country' =>'required',
            'mail' =>'required',
            'accounting_vats_id' =>'required',
            'curency' =>'required',
            'add_day_validity_quote' =>'required',
            'add_delivery_delay_order'  =>'required',
        ];
    }
}
