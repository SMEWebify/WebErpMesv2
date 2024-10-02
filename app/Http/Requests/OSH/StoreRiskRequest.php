<?php

namespace App\Http\Requests\OSH;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskRequest extends FormRequest
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
            'severity' => 'required|in:1,2,3',
            'probability' => 'required|in:1,2,3',
            'preventive_measures' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
