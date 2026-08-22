<?php

namespace App\Http\Requests\HumanResources;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('human-resources-menu') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('leave_types', 'code')],
            'label' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'counts_against_balance' => 'nullable|boolean',
            'default_annual_quota' => 'nullable|numeric|min:0|max:365',
            'ordre' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
        ];
    }
}
