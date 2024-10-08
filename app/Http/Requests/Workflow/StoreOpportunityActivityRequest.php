<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opportunities_id' => 'required|integer|exists:opportunities,id',
            'label' => 'required|string|max:255',
            'type' => 'required|integer|in:1,2,3,4,5',
            'priority' => 'required|integer|in:1,2,3,4',
            'due_date' => 'nullable|date',
            'comment' => 'nullable|string',
        ];
    }
}
