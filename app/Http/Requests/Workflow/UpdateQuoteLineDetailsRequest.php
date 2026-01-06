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
            'x_size' => 'nullable|numeric|min:0',
            'y_size' => 'nullable|numeric|min:0',
            'z_size' => 'nullable|numeric|min:0',
            'x_oversize' => 'nullable|numeric|min:0',
            'y_oversize' => 'nullable|numeric|min:0',
            'z_oversize' => 'nullable|numeric|min:0',
            'diameter' => 'nullable|numeric|min:0',
            'diameter_oversize' => 'nullable|numeric|min:0',
            'material' => 'nullable|string|max:255',
            'thickness' => 'nullable|numeric|min:0',
            'finishing' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'bend_count' => 'nullable|integer|min:0',
            'material_loss_rate' => 'nullable|numeric|min:0|max:100',
            'cad_file' => 'nullable|string|max:255',
            'cam_file' => 'nullable|string|max:255',
            'cad_file_path' => 'nullable|string|max:255',
            'cam_file_path' => 'nullable|string|max:255',
            'internal_comment' => 'nullable|string|max:255',
            'external_comment' => 'nullable|string|max:255',
            'custom_requirements' => 'nullable|array',
            'custom_requirements.*.label' => 'nullable|string|max:255',
            'custom_requirements.*.value' => 'nullable|string|max:255',
            'product_custom_fields' => 'nullable|array',
            'product_custom_fields.*' => 'nullable|string|max:255',
        ];
    }
}
