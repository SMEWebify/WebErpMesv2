<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdressRequest extends FormRequest
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
            'ordre' =>'required|numeric|gt:0',
            'label'=>'required',
            'adress'=>'required',
            'zipcode'=>'required',
            'city'=>'required',
            'country'=>'required',
            'number'=>'nullable|string',
            'mail'=>'nullable|string',
        ];
    }
}
