<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
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
            'code' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'companies_id' => 'required|exists:companies,id',
            'companies_contacts_id' => 'required|exists:companies_contacts,id',
            'companies_addresses_id' => 'required|exists:companies_addresses,id',
            'accounting_payment_conditions_id' => 'required|exists:accounting_payment_conditions,id',
            'accounting_payment_methods_id' => 'required|exists:accounting_payment_methods,id',
            'accounting_deliveries_id' => 'required|exists:accounting_deliveries,id',
            'customer_reference' => 'nullable|string|max:255',
            'validity_date' => 'nullable|date',
            'statu' => 'nullable|string',
            'comment' => 'nullable|string',
            'opportunities_id' => 'nullable|exists:opportunities,id',
        ];
    }
}
