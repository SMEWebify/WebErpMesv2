<?php

namespace App\Http\Requests\Planning;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'label'=>'required',
            'ORDER'=>'required',
            'methods_services_id'=>'required|numeric',
            'component_id'=>'numeric',
            'type'=>'required|numeric',
            'unit_cost'=>'required',
            'unit_price'=>'required',
        ];
    }
}
