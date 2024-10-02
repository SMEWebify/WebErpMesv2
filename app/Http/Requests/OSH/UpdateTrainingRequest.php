<?php

namespace App\Http\Requests\OSH;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRequest extends FormRequest
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
            'type_of_training' => 'required|string|max:255',
            'training_date' => 'required|date',
            'expiration_date' => 'nullable|date',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
