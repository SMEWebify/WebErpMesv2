<?php

namespace App\Http\Requests\Times;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'absence_type' => 'required|integer|between:1,4',
            'absence_type_day' => 'required|integer|between:1,3',
            'hours_count' => 'nullable|numeric|min:0|max:24|required_if:absence_type,4',
            'comment' => 'nullable|string|max:255',
            'statu' => 'nullable|integer|between:1,3',
            'start_date'=>'required|date',
            'end_date'=>'required|date|after_or_equal:start_date',
        ];
    }
}
