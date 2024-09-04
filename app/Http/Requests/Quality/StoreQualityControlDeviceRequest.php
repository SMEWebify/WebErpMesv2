<?php

namespace App\Http\Requests\Quality;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityControlDeviceRequest extends FormRequest
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
            'code' =>'required|unique:quality_control_devices',
            'label'=>'required',
            'serial_number'=>'required|unique:quality_control_devices',
            'picture'=>'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'service_id'=>'required',
        ];
    }
}
