<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFactoryRequest extends FormRequest
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
        $pdfThemes = array_keys(config('pdf.themes', []));

        return [
            //
            'name' => 'required',
            'address' => 'required',
            'city' => 'required',
            'country' => 'required',
            'mail' => 'required',
            'accounting_vats_id' => 'required',
            'curency' => 'required',
            'add_day_validity_quote' => 'required',
            'add_delivery_delay_order'  => 'required',
            'pdf_theme' => ['required', Rule::in($pdfThemes)],
            'pdf_custom_css' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
