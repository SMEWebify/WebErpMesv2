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

            // Metadata — règles métier par endpoint. Toutes optionnelles :
            // meta() applique les défauts (IntegrationEndpoint::META_DEFAULTS)
            // si absentes du payload.
            'metadata'                                    => ['nullable', 'array'],
            'metadata.status_transition_from'             => ['nullable', 'string', Rule::in(['OPEN','IN_PROGRESS','DELIVERED','PARTLY_DELIVERED','STOPPED','CANCELED'])],
            'metadata.status_transition_to'               => ['nullable', 'string', Rule::in(['OPEN','IN_PROGRESS','DELIVERED','PARTLY_DELIVERED','STOPPED','CANCELED'])],
            'metadata.send_tasks'                         => ['nullable', 'boolean'],
            'metadata.job_status_on_send'                 => ['nullable', 'string', 'max:50'],
            'metadata.default_priority'                   => ['nullable', 'integer', 'between:1,5'],
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
            'metadata'   => $this->normalizeMetadata($this->input('metadata')),
        ]);
    }

    /**
     * Normalise le tableau metadata : cast booleans/ints depuis strings
     * (les switches/inputs HTML envoient tout en string), drop les clés vides
     * pour laisser meta() appliquer les défauts plutôt que persister "".
     */
    private function normalizeMetadata(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        if (isset($value['status_transition_from']) && $value['status_transition_from'] !== '') {
            $normalized['status_transition_from'] = (string) $value['status_transition_from'];
        }
        if (isset($value['status_transition_to']) && $value['status_transition_to'] !== '') {
            $normalized['status_transition_to'] = (string) $value['status_transition_to'];
        }
        if (array_key_exists('send_tasks', $value)) {
            $normalized['send_tasks'] = filter_var($value['send_tasks'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        if (isset($value['job_status_on_send']) && $value['job_status_on_send'] !== '') {
            $normalized['job_status_on_send'] = (string) $value['job_status_on_send'];
        }
        if (isset($value['default_priority']) && $value['default_priority'] !== '') {
            $normalized['default_priority'] = (int) $value['default_priority'];
        }

        return $normalized;
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
