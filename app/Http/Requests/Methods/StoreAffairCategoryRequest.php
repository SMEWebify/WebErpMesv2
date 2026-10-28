<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class StoreAffairCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'  => 'required|string|max:30|unique:affair_categories,code',
            'label' => 'required|string|max:255',
            'ordre' => 'nullable|integer|min:0',
            'required_services'   => 'nullable|array',
            'required_services.*' => 'integer|exists:methods_services,id',
        ];
    }
}
