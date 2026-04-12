<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingNotificationRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'companies_notification'              => 'nullable|boolean',
            'companies_email_notification'        => 'nullable|boolean',
            'users_notification'                  => 'nullable|boolean',
            'users_email_notification'            => 'nullable|boolean',
            'quotes_notification'                 => 'nullable|boolean',
            'quotes_email_notification'           => 'nullable|boolean',
            'orders_notification'                 => 'nullable|boolean',
            'orders_email_notification'           => 'nullable|boolean',
            'non_conformity_notification'         => 'nullable|boolean',
            'non_conformity_email_notification'   => 'nullable|boolean',
            'return_notification'                 => 'nullable|boolean',
            'return_email_notification'           => 'nullable|boolean',
            'pre_order_notification'              => 'nullable|boolean',
            'pre_order_email_notification'        => 'nullable|boolean',
        ];
    }
}
