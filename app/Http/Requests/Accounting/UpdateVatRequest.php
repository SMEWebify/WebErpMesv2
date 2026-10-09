<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVatRequest extends FormRequest
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
            'label'=>'required',
            'rate'=>['required', StoreVatRequest::uniqueRateInCategory($this->input('en16931_category'))->ignore($this->id)],
            'default'=>'integer',
            'en16931_category'=>'nullable|string|max:4',
            'exemption_reason_code'=>'nullable|string|max:255',
            'exemption_reason_text'=>'nullable|string',
            'legal_mention'=>'nullable|string',
        ];
    }
}
