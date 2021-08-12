<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicesRequest extends FormRequest
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
            'CODE' =>'required|unique:methods_services',
            'ORDRE' =>'required',
            'LABEL'=>'required',
            'HOURLY_RATE'=>'required',
            'MARGIN'=>'required',
        ];
    }
}
