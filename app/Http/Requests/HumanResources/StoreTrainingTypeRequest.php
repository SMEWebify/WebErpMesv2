<?php

namespace App\Http\Requests\HumanResources;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('osh-menu') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('training_types', 'code')],
            'label' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'validity_months' => 'nullable|integer|min:0|max:600',
            'ordre' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
            'resources' => 'nullable|array',
            'resources.*' => 'integer|exists:methods_ressources,id',
        ];
    }
}
