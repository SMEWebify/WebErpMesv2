<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteLineDetailsRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'x_size' => 'required|numeric|min:0',
            'y_size' => 'required|numeric|min:0',
            'z_size' => 'required|numeric|min:0',
            'x_oversize' => 'nullable|numeric|min:0',
            'y_oversize' => 'nullable|numeric|min:0',
            'z_oversize' => 'nullable|numeric|min:0',
            'diameter' => 'nullable|numeric|min:0',
            'diameter_oversize' => 'nullable|numeric|min:0',
            'material' => 'nullable|string|max:255',
            'thickness' => 'nullable|numeric|min:0',
            'finishing' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'material_loss_rate' => 'nullable|numeric|min:0|max:100',
            'internal_comment' => 'nullable|string|max:255',
            'external_comment' => 'nullable|string|max:255',
        ];
    }
}
