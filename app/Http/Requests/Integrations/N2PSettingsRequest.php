<?php

namespace App\Http\Requests\Integrations;

use Illuminate\Foundation\Http\FormRequest;

class N2PSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'n2p_enabled' => ['required', 'boolean'],
            'n2p_base_url' => ['nullable', 'url'],
            'n2p_api_token' => ['nullable', 'string'],
            'n2p_send_on_order_status_from' => ['required', 'string', 'max:191'],
            'n2p_send_on_order_status_to' => ['required', 'string', 'max:191'],
            'n2p_job_status_on_send' => ['required', 'string', 'max:191'],
            'n2p_priority_default' => ['required', 'integer', 'between:1,5'],
            'n2p_send_tasks' => ['required', 'boolean'],
            'n2p_verify_ssl' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'n2p_enabled' => $this->toBoolean($this->input('n2p_enabled')),
            'n2p_send_tasks' => $this->toBoolean($this->input('n2p_send_tasks')),
            'n2p_verify_ssl' => $this->toBoolean($this->input('n2p_verify_ssl')),
        ]);
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
