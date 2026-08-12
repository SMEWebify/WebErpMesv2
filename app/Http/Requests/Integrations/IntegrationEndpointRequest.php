<?php

namespace App\Http\Requests\Integrations;

use App\Models\Integrations\IntegrationEndpoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntegrationEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $existing = $this->route('endpoint');
        $existingId = $existing instanceof IntegrationEndpoint ? $existing->id : null;

        // system_code + direction identifient un endpoint côté dispatch
        // (IntegrationDispatcher::forSystem). Doivent être uniques ensemble
        // et — sur update — non modifiables (sinon on orpheline les deliveries
        // et on casse tout le routing HMAC).
        $systemCodeRules = ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'];
        $directionRules = ['required', Rule::in([
            IntegrationEndpoint::DIRECTION_INBOUND,
            IntegrationEndpoint::DIRECTION_OUTBOUND,
        ])];

        if ($existing) {
            $systemCodeRules[] = Rule::in([$existing->system_code]);
            $directionRules[] = Rule::in([$existing->direction]);
        } else {
            $systemCodeRules[] = Rule::unique('integration_endpoints', 'system_code')
                ->where(fn ($q) => $q->where('direction', $this->input('direction')));
        }

        return [
            'name'                  => ['required', 'string', 'max:191'],
            'system_code'           => $systemCodeRules,
            'direction'             => $directionRules,
            // http:// autorisé UNIQUEMENT si verify_ssl=false explicitement
            // (usage dev/local) — sinon on force https pour ne pas leaker
            // bearer/HMAC en clair sur le réseau.
            'url'                   => ['nullable', 'url:http,https', 'max:500'],
            'auth_method'           => ['required', Rule::in([
                IntegrationEndpoint::AUTH_NONE,
                IntegrationEndpoint::AUTH_BEARER,
                IntegrationEndpoint::AUTH_HMAC,
                IntegrationEndpoint::AUTH_BEARER_HMAC,
            ])],
            'bearer_token'          => ['nullable', 'string', 'max:500'],
            'hmac_secret'           => ['nullable', 'string', 'max:500'],
            'hmac_header'           => ['nullable', 'string', 'max:100'],
            'timestamp_header'      => ['nullable', 'string', 'max:100'],
            'timestamp_tolerance_s' => ['nullable', 'integer', 'between:30,3600'],
            'verify_ssl'            => ['required', 'boolean'],
            'events'                => ['nullable', 'array'],
            'events.*'              => ['string', 'max:100'],
            'is_active'             => ['required', 'boolean'],
            'retry_max'             => ['nullable', 'integer', 'between:0,20'],
            'retry_backoff'         => ['nullable', 'array'],
            'retry_backoff.*'       => ['integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $url = (string) $this->input('url', '');
            $verifySsl = filter_var($this->input('verify_ssl'), FILTER_VALIDATE_BOOLEAN);
            if ($url !== '' && str_starts_with(strtolower($url), 'http://') && $verifySsl) {
                $validator->errors()->add('url', "URL en http:// interdite si verify_ssl est actif — bearer/HMAC circuleraient en clair.");
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'verify_ssl' => filter_var($this->input('verify_ssl'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'events'     => $this->normalizeList($this->input('events')),
        ]);
    }

    private function normalizeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), static fn ($v) => $v !== ''));
        }
        if (is_string($value) && $value !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($v) => $v !== ''));
        }
        return [];
    }
}
