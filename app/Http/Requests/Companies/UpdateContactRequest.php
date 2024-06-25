<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
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
            'ordre' =>'required|numeric|gt:0',
            'civility'=>'nullable|string',
            'first_name'=>'required',
            'name'=>'required',
            'function'=>'nullable|string',
            'number'=>'nullable|string',
            'mobile'=>'nullable|string',
            'mail'=>'nullable|string',
        ];
    }
}
