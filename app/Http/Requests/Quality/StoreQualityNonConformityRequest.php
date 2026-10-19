<?php

namespace App\Http\Requests\Quality;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityNonConformityRequest extends FormRequest
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
            'code'              =>'required|unique:quality_non_conformities',
            'label'             => 'required|string|max:255',
            'statu'             => 'nullable|in:1,2,3,4',
            'type'              => 'nullable',
            'user_id'           => 'required|exists:users,id',
            'service_id'        => 'nullable|exists:services,id',
            'failure_id'        => 'nullable|exists:failures,id',
            'failure_comment'   => 'nullable|string',
            'causes_id'         => 'nullable|exists:causes,id',
            'causes_comment'    => 'nullable|string',
            'correction_id'     => 'nullable|exists:corrections,id',
            'correction_comment' => 'nullable|string',
            'companie_id'       => 'nullable|exists:companies,id',
            'order_lines_id'    => 'nullable|exists:order_lines,id',
            'ind'               => 'nullable|string|max:50',
            'task_id'           => 'nullable|exists:tasks,id',
            'qty'               => 'nullable|numeric|min:1',
            'resolution_date'   => 'nullable|date',
        ];
    }
}
