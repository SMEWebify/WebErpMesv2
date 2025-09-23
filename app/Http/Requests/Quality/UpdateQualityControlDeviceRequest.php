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
            'service_id' => 'required',
            'user_id' => 'required',
            'calibrated_at' => 'nullable|date',
            'calibration_due_at' => 'nullable|date|after_or_equal:calibrated_at',
            'calibration_status' => 'nullable|string|max:255',
            'calibration_provider' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'capability_index' => 'nullable|numeric|min:0',
        ];
    }
}
