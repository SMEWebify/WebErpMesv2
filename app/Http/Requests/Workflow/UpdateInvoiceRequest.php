<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
            'companies_id'           => 'required|exists:companies,id',
            'companies_addresses_id' => 'nullable|exists:companies_addresses,id',
            'companies_contacts_id'  => 'nullable|exists:companies_contacts,id',
        ];
    }
}
