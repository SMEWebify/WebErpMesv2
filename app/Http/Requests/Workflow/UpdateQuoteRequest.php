<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'label' => 'required|string|max:255',
            'customer_reference' => 'nullable|string|max:255',
            'companies_id' => 'required|exists:companies,id',
            'companies_contacts_id' => 'required|exists:companies_contacts,id',
            'companies_addresses_id' => 'required|exists:companies_addresses,id',
            'validity_date' => 'nullable|date',
            'accounting_payment_conditions_id' => 'required|exists:accounting_payment_conditions,id',
            'accounting_payment_methods_id' => 'required|exists:accounting_payment_methods,id',
            'accounting_deliveries_id' => 'required|exists:accounting_deliveries,id',
            'comment' => 'nullable|string',
            'reviewed_by' => 'nullable|exists:users,id',
            'reviewed_at' => 'nullable|date',
            'review_decision' => 'nullable|in:pending,approved,rejected',
            'change_requested_by' => 'nullable|exists:users,id',
            'change_reason' => 'nullable|string',
            'change_approved_at' => 'nullable|date',
        ];
    }
}
