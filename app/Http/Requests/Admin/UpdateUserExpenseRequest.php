<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserExpenseRequest extends FormRequest
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
            //
            'category_id'  =>'required',
            'expense_date'  =>'required',
            'location'  =>'required',
            'description'  =>'required',
            'amount'  =>'required',
            'payer_id'  =>'required|numeric|gt:0',
            'tax'  =>'required|numeric|gt:0',
        ];
    }
}
