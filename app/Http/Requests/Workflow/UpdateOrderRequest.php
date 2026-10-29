<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'companies_id' => 'required_if:type,1|nullable|exists:companies,id',
            'companies_contacts_id' => 'required_if:type,1|nullable|exists:companies_contacts,id',
            'companies_addresses_id' => 'required_if:type,1|nullable|exists:companies_addresses,id',
            'validity_date' => 'nullable|date',
            'accounting_payment_conditions_id' => 'required_if:type,1|nullable|exists:accounting_payment_conditions,id',
            'accounting_payment_methods_id' => 'required_if:type,1|nullable|exists:accounting_payment_methods,id',
            'accounting_deliveries_id' => 'required_if:type,1|nullable|exists:accounting_deliveries,id',
            'comment' => 'nullable|string',
            // Revue de contrat et demande de changement : onglet dédié
            // (OrderContractReviewController), jamais par ce formulaire.
            // Absent du formulaire tant que le référentiel est vide : ne rien écraser.
            'affair_category_id' => 'sometimes|nullable|integer|exists:affair_categories,id',
        ];
    }
}
