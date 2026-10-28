<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le code n'est pas modifiable : il identifie le motif hors de l'écran (motifs
 * insérés par la migration, import, export des statistiques).
 */
class UpdateLossReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'  => 'required|string|max:255',
            'ordre'  => 'nullable|integer|min:0',
            'active' => 'boolean',
        ];
    }
}
