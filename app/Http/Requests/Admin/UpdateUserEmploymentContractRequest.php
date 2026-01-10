<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserEmploymentContractRequest extends FormRequest
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
            'id' => 'required|exists:user_employment_contracts,id',
            'statu' => 'nullable',
            'methods_section_id' => 'nullable|exists:methods_sections,id',
            'signature_date' => 'nullable|date',
            'type_of_contract' => 'nullable|string',
            'start_date' => 'nullable|date',
            'duration_trial_period' => 'nullable|integer',
            'end_date' => 'nullable|date',
            'weekly_duration' => 'nullable|integer',
            'position' => 'nullable|string',
            'coefficient' => 'nullable|string',
            'hourly_gross_salary' => 'nullable|numeric',
            'minimum_monthly_salary' => 'nullable|integer',
            'annual_gross_salary' => 'nullable|integer',
            'end_of_contract_reason' => 'nullable|string',
        ];
    }
}
