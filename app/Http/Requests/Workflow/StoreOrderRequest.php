<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            //
            'code' =>'required|unique:orders',
            'label'=>'required',
            'companies_id'=>'required',
            'companies_contacts_id'=>'required',
            'companies_addresses_id'=>'required',
            'accounting_payment_conditions_id'=>'required',
            'accounting_payment_methods_id'=>'required',
            'accounting_deliveries_id'=>'required',
        ];
    }
}
