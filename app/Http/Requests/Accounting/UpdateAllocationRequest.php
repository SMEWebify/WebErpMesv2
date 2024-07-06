<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllocationRequest extends FormRequest
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
            'label'=>'required|string',
            'accounting_vats_id'=>'required',
            'vat_account'=>'integer',
            'code_account'=>'integer',
            'type_imputation'=>'integer',
        ];
    }
}
