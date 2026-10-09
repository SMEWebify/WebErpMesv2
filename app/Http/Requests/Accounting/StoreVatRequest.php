<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Foundation\Http\FormRequest;

class StoreVatRequest extends FormRequest
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
            'code' =>'required|unique:accounting_vats',
            'label'=>'required',
            'rate'=>['required', self::uniqueRateInCategory($this->input('en16931_category'))],
            'en16931_category'=>'nullable|string|max:4',
            'exemption_reason_code'=>'nullable|string|max:255',
            'exemption_reason_text'=>'nullable|string',
            'legal_mention'=>'nullable|string',
        ];
    }

    /**
     * Un taux unique par catégorie EN 16931, et non plus unique tout court : la
     * matrice de TVA a besoin de plusieurs codes à 0 % (intracom K, export G,
     * autoliquidation AE, franchise E).
     */
    public static function uniqueRateInCategory(?string $category): Unique
    {
        $category = $category ?: null;

        return Rule::unique('accounting_vats', 'rate')->where(fn ($query) => $category === null
            ? $query->whereNull('en16931_category')
            : $query->where('en16931_category', $category));
    }
}
