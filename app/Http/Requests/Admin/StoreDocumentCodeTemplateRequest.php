<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentCodeTemplateRequest extends FormRequest
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
            'document_type' => 'required|string|max:255|unique:document_code_templates,document_type',
            'template' => 'required|string|max:255',
            'reset_period'        => 'required|in:none,daily,weekly,monthly,yearly',
            'yearly_reset_month'  => 'required_if:reset_period,yearly|integer|between:1,12',
            'yearly_reset_day'    => 'required_if:reset_period,yearly|integer|between:1,31',
            'id_padding'          => 'nullable|integer|between:0,10',
        ];
    }
}
