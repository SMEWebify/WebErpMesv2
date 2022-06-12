<?php

namespace App\Http\Requests\Times;

use Illuminate\Foundation\Http\FormRequest;

class StoreMachineEventRequest extends FormRequest
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
            'code' =>'required|unique:times_machine_events',
            'ordre' =>'required|numeric|gt:0',
            'label'=>'required',
        ];
    }
}
