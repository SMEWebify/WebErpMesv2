<?php

namespace App\Http\Requests\Quality;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityAmdecRequest extends FormRequest
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
            'product_id' => 'required',
            'user_id' => 'required',
            'failure_mode' => 'required|string|max:255',
            'effect' => 'required|string',
            'cause' => 'required|string',
            'severity' => 'required|integer|min:1|max:10',
            'occurrence' => 'required|integer|min:1|max:10',
            'detection' => 'required|integer|min:1|max:10',
            'current_control' => 'nullable|string',
            'recommended_action' => 'nullable|string',
        ];
    }
}
