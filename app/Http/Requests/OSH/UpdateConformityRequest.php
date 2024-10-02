<?php

namespace App\Http\Requests\OSH;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConformityRequest extends FormRequest
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
            'document_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expiration_date' => 'nullable|date',
            'section_id' => 'required|exists:methods_sections,id',
            'user_id' => 'required|exists:users,id',
            'statut' => 'required|in:1,2,3',
        ];
    }
}
