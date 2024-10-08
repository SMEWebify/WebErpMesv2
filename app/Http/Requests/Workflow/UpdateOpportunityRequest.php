<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityRequest extends FormRequest
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
            'companies_id' => 'required|exists:companies,id',
            'companies_contacts_id' => 'required|exists:companies_contacts,id',
            'companies_addresses_id' => 'required|exists:companies_addresses,id',
            'label' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'close_date' => 'nullable|date',
            'probality' => 'required|integer|min:0|max:100',
            'comment' => 'nullable|string',
        ];
    }
}