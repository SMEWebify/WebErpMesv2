<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRessourceRequest extends FormRequest
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
            'capacity'=>'required',
            'section_id'=>'required',
            'methods_services_id'=>'required',
            'picture'=>'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ];
    }
}
