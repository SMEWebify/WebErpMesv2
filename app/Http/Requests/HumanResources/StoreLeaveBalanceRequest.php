<?php

namespace App\Http\Requests\HumanResources;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveBalanceRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'period_start' => 'required|date',
            'entitled_days' => 'nullable|numeric|min:0|max:365',
            'carried_over_days' => 'nullable|numeric|min:-365|max:365',
            'adjustment_days' => 'nullable|numeric|min:-365|max:365',
            'comment' => 'nullable|string|max:255',
        ];
    }
}
