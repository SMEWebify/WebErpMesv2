<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicesRequest extends FormRequest
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
            // « * » et « | » séparent les champs des codes de scan N2P:{of}*{ligne}*{op}*{seq}.
            'code' =>['required', 'unique:methods_services', 'not_regex:/[*|]/'],
            'ordre' =>'required|numeric|gt:0',
            'label'=>'required',
            'hourly_rate'=>'required',
            'margin'=>'required',
            'picture'=>'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'companies_ids'   => 'nullable|array',
            'companies_ids.*' => 'integer|exists:companies,id',
            'is_nesting'      => 'nullable|boolean',
            'is_bending'      => 'nullable|boolean',
            'requires_release' => 'nullable|boolean',
        ];
    }
}
