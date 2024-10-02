<?php

namespace App\Http\Requests\OSH;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
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
            'incident_date' => 'required|date',
            'description' => 'required|string|max:255',
            'severity' => 'required|in:1,2,3',
            'corrective_actions' => 'nullable|string',
            'resolution_date' => 'nullable|date',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
