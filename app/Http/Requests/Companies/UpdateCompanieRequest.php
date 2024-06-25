<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanieRequest extends FormRequest
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
            'label'=>'required',
            'user_id'=>'required',
            'civility' => 'nullable|string',
            'last_name'=> 'nullable|string',
            'website'=> 'nullable|string',
            'fbsite'=> 'nullable|string',
            'twittersite'=> 'nullable|string', 
            'lkdsite'=> 'nullable|string',
            'siren'=> 'nullable|string', 
            'naf_code'=> 'nullable|string', 
            'intra_community_vat'=> 'nullable|string',
            'statu_customer'=>'required',
            'discount'=> 'nullable|numeric',
            'account_general_customer'=> 'nullable|string',
            'account_auxiliary_customer'=> 'nullable|string',
            'statu_supplier'=>'required',
            'account_general_supplier'=> 'nullable|string',
            'account_auxiliary_supplier'=> 'nullable|string',
            'recept_controle'=>'required',
            'comment'=> 'nullable|string',
            'barcode_value'=> 'nullable|string',
        ];
    }
}
