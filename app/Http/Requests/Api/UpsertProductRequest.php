<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            // Code optionnel : généré automatiquement via DocumentCodeGenerator si absent
            'code'                => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'code')->ignore($productId),
            ],
            'label'               => 'required|string|max:255',

            // Résolution flexible : code (string) prioritaire, id en fallback, sinon défauts serveur
            'service_code'        => 'nullable|string|max:255',
            'family_code'         => 'nullable|string|max:255',
            'unit_code'           => 'nullable|string|max:255',
            'methods_services_id' => 'nullable|exists:methods_services,id',
            'methods_families_id' => 'nullable|exists:methods_families,id',
            'methods_units_id'    => 'nullable|exists:methods_units,id',

            'sold'                => 'nullable|in:1,2',
            'purchased'           => 'nullable|in:1,2',
            'tracability_type'    => 'nullable|in:1,2,3',

            'ind'                 => 'nullable|string|max:50',
            'material'            => 'nullable|string|max:255',
            'finishing'           => 'nullable|string|max:255',
            'thickness'           => 'nullable|numeric|min:0',
            'weight'              => 'nullable|numeric|min:0',
            'bend_count'          => 'nullable|integer|min:0',

            'x_size'              => 'nullable|numeric|min:0',
            'y_size'              => 'nullable|numeric|min:0',
            'z_size'              => 'nullable|numeric|min:0',
            'x_oversize'          => 'nullable|numeric|min:0',
            'y_oversize'          => 'nullable|numeric|min:0',
            'z_oversize'          => 'nullable|numeric|min:0',
            'diameter'            => 'nullable|numeric|min:0',
            'diameter_oversize'   => 'nullable|numeric|min:0',
            'section_size'        => 'nullable|numeric|min:0',

            'qty_eco_min'         => 'nullable|numeric|min:0',
            'qty_eco_max'         => 'nullable|numeric|min:0',
            'purchased_price'     => 'nullable|numeric|min:0',
            'selling_price'       => 'nullable|numeric|min:0',

            'comment'             => 'nullable|string',
            'picture'             => 'nullable|string|max:255',
            'drawing_file'        => 'nullable|string|max:255',
            'stl_file'            => 'nullable|string|max:255',
            'svg_file'            => 'nullable|string|max:255',
            'cad_file_path'       => 'nullable|string|max:255',
            'cam_file_path'       => 'nullable|string|max:255',
            'csv_file_name'       => 'nullable|string|max:255',
        ];
    }
}
