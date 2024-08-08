<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
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
            //
            'companies_id'=>'required',
            'companies_contacts_id'=>'required',
            'companies_addresses_id'=>'required',
            'user_id'=>'required',
            'source'=>'nullable|string',
            'priority'=>'integer',
            'campaign'=>'nullable|string',
            'comment'=>'nullable|string',
        ];
    }
}
