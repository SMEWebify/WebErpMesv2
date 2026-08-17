<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRessourceRequest extends FormRequest
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
            'ordre' =>'required|numeric|gt:0',
            'label'=>'required',
            'capacity'=>'required',
            'section_id'=>'required',
            'methods_services_id'=>'required|exists:methods_services,id',
            // Services complémentaires réalisables par la ressource (pivot methods_ressource_service).
            'additional_services'=>'nullable|array',
            'additional_services.*'=>'exists:methods_services,id',
            // Nature de la capacité : machine ou main-d'oeuvre, et quotité d'opérateurs consommée.
            'is_labor'=>'nullable|boolean',
            'labor_ratio'=>'nullable|numeric|min:0|max:99',
            'qualified_users'=>'nullable|array',
            'qualified_users.*'=>'exists:users,id',
            'work_shift_pattern_id'=>'nullable|exists:work_shift_patterns,id',
            'picture'=>'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ];
    }

    /**
     * Le composant switch d'AdminLTE poste la chaîne "true" quand il est activé,
     * et rien du tout quand il ne l'est pas. La règle `boolean` n'accepte que
     * true/false/1/0 : on normalise la valeur ici plutôt que d'assouplir la règle.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_labor' => $this->boolean('is_labor')]);
    }
}
