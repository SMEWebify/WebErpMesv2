<?php

namespace App\Http\Requests\Companies;

class UpdateCompanieJsonRequest extends UpdateCompanieRequest
{
    /**
     * Override rules for JSON endpoint: fields that are required in the Blade
     * form can be null here (e.g. a company without an assigned user).
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'user_id'              => 'nullable',
            'statu_customer'       => 'nullable',
            'statu_supplier'       => 'nullable',
            'recept_controle'      => 'nullable',
            'delivery_constraint'  => 'nullable|numeric',
            'tolerance_days'       => 'nullable|numeric',
            // Plafond d'encours : retiré à l'enregistrement si l'utilisateur
            // n'a pas credit-limit-override (CompaniesController::updateJson).
            'credit_limit'         => 'nullable|numeric|min:0|max:9999999999999',
            'credit_block_level'   => 'nullable|in:none,warn,block',
        ]);
    }
}
