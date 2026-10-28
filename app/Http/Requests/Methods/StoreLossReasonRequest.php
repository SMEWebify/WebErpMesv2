<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class StoreLossReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'  => 'required|string|max:30|unique:loss_reasons,code',
            'label' => 'required|string|max:255',
            'ordre' => 'nullable|integer|min:0',
        ];
    }
}
