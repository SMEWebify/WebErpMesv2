<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class StoreRessourceRequest extends FormRequest
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
            'ORDRE' =>'required',
            'CODE' =>'required|unique:methods_ressources',
            'LABEL'=>'required',
            'CAPACITY'=>'required',
            'section_id'=>'required',
            'service_id'=>'required',
        ];
    }
}
