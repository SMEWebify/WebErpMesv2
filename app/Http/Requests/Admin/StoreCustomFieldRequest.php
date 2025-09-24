<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomFieldRequest extends FormRequest
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
            'name' => 'required|string',
            'type' => 'required|string|in:text,number,checkbox,date,select',
            'related_type' => 'required|string|in:quote,order,delivery,invoice,purchase,product',
            'category' => 'nullable|string|max:255',
            'options' => ['nullable', 'string', 'required_if:type,select'],
        ];
    }
}
