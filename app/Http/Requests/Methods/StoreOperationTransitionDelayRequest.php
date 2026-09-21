<?php

namespace App\Http\Requests\Methods;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationTransitionDelayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'from_service_id' => [
                'required',
                'integer',
                'exists:methods_services,id',
                'different:to_service_id',
                Rule::unique('operation_transition_delays', 'from_service_id')
                    ->where(fn ($q) => $q->where('to_service_id', $this->input('to_service_id'))),
            ],
            'to_service_id'  => ['required', 'integer', 'exists:methods_services,id'],
            'transfer_hours' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }
}
