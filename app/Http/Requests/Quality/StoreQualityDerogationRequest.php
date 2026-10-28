<?php

namespace App\Http\Requests\Quality;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityDerogationRequest extends FormRequest
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
            'code' =>'required|unique:quality_derogations',
            'label'=>'required',
            'user_id'=>'required',
            'statu' => 'nullable|in:1,2,3,4',
            'customer_reply_date' => 'nullable|date',
            'customer_signatory' => 'nullable|string|max:255',
            'companies_id' => 'nullable|integer',
            'companies_contacts_id' => 'nullable|integer',
            'order_lines_id' => 'nullable|integer',
        ];
    }
}
