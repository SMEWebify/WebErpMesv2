<?php

namespace App\Http\Requests\Quality;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQualityControlDeviceRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            //
            'label'=>'required',
            'serial_number'=>'required|unique:quality_control_devices,serial_number,'. $this->id,
            'picture'=>'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ];
    }
}
