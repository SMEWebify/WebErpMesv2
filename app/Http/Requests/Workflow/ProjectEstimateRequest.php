<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class ProjectEstimateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_requirements' => 'nullable|string',
            'layout_plan' => 'nullable|string',
            'materials_finishes' => 'nullable|string',
            'logistics' => 'nullable|string',
            'logistics_cost' => 'nullable|numeric',
            'coordination_with_contractors' => 'nullable|string',
            'contractors_cost' => 'nullable|numeric',
            'waste_management' => 'nullable|string',
            'waste_management_cost' => 'nullable|numeric',
            'taxes_and_fees' => 'nullable|string',
            'taxes_cost' => 'nullable|numeric',
            'work_start_date' => 'nullable|date',
            'work_end_date' => 'nullable|date',
            'contingency_days' => 'nullable|integer',
            'options_variants' => 'nullable|string',
            'insurance_liability' => 'nullable|string',
            'insurance_cost' => 'nullable|numeric',
        ];
    }
}
