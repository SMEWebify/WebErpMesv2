<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryRequest extends FormRequest
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
            'label'                  => 'required',
            'tracking_number'        => 'nullable|string',
            'comment'                => 'nullable|string',
            'customer_reference'     => 'nullable|string|max:255',
            'companies_id'           => 'nullable|integer|exists:companies,id',
            'companies_addresses_id' => 'nullable|integer|exists:companies_addresses,id',
            'companies_contacts_id'  => 'nullable|integer|exists:companies_contacts,id',
        ];
    }
}