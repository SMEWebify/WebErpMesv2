<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperationTransitionDelayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'transfer_hours' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }
}
