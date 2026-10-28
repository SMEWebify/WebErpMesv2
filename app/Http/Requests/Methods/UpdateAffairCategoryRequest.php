<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le code n'est pas modifiable : il peut servir de clé à un import ou à un
 * paramétrage (opérations obligatoires par catégorie).
 */
class UpdateAffairCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'  => 'required|string|max:255',
            'ordre'  => 'nullable|integer|min:0',
            'active' => 'boolean',
        ];
    }
}
