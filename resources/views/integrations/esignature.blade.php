@extends('adminlte::page')

@section('title', __('esignature.title'))

@section('content_header')
    <h1>
        <i class="fas fa-file-signature mr-1"></i> {{ __('esignature.title') }}
        <small class="text-muted">{{ __('esignature.subtitle') }}</small>
    </h1>
@stop

@section('content')

    @if(session('success'))
        <x-adminlte-alert theme="success" dismissable>{{ session('success') }}</x-adminlte-alert>
    @endif

    @if($errors->any())
        <x-adminlte-alert theme="danger" dismissable>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('esignature.config') }}</h3>
                    <div class="card-tools">
                        @if($setting?->isUsable())
                            <span class="badge badge-success">{{ __('general_content.active_trans_key') }}</span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.integrations.esignature.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('esignature.provider') }}</label>
                                    <input type="text" class="form-control" value="DocuSign" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="environment">{{ __('esignature.environment') }}</label>
                                    <select id="environment" name="environment" class="form-control" required>
                                        <option value="demo" @selected(old('environment', $setting->environment ?? 'demo') === 'demo')>{{ __('esignature.environment_demo') }}</option>
                                        <option value="production" @selected(old('environment', $setting->environment ?? 'demo') === 'production')>{{ __('esignature.environment_production') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="integration_key">{{ __('esignature.integration_key') }}</label>
                            <input type="text" id="integration_key" name="integration_key" class="form-control" required
                                   autocomplete="off" value="{{ old('integration_key', $setting->integration_key ?? '') }}"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            <small class="form-text text-muted">{{ __('esignature.integration_key_hint') }}</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="api_user_id">{{ __('esignature.api_user_id') }}</label>
                                    <input type="text" id="api_user_id" name="api_user_id" class="form-control" required
                                           autocomplete="off" value="{{ old('api_user_id', $setting->api_user_id ?? '') }}">
                                    <small class="form-text text-muted">{{ __('esignature.api_user_id_hint') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_id">{{ __('esignature.account_id') }}</label>
                                    <input type="text" id="account_id" name="account_id" class="form-control" required
                                           autocomplete="off" value="{{ old('account_id', $setting->account_id ?? '') }}">
                                    <small class="form-text text-muted">{{ __('esignature.account_id_hint') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="private_key">
                                {{ __('esignature.private_key') }}
                                @if($has_private_key)
                                    <small class="text-success">({{ __('esignature.secret_keep_hint') }})</small>
                                @endif
                            </label>
                            <textarea id="private_key" name="private_key" class="form-control text-monospace" rows="5"
                                      autocomplete="off" spellcheck="false" @if(! $has_private_key) required @endif
                                      placeholder="{{ $has_private_key ? '••••••••' : '-----BEGIN RSA PRIVATE KEY-----' }}"></textarea>
                            <small class="form-text text-muted">{{ __('esignature.private_key_hint') }}</small>
                        </div>

                        <div class="form-group">
                            <label for="hmac_key">
                                {{ __('esignature.hmac_key') }}
                                @if($has_hmac_key)
                                    <small class="text-success">({{ __('esignature.secret_keep_hint') }})</small>
                                @endif
                            </label>
                            <input type="password" id="hmac_key" name="hmac_key" class="form-control"
                                   autocomplete="new-password" placeholder="{{ $has_hmac_key ? '••••••••' : '' }}">
                            <small class="form-text text-muted">{{ __('esignature.hmac_key_hint') }}</small>
                            @if($has_hmac_key)
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" id="clear_hmac_key" name="clear_hmac_key" value="1" class="custom-control-input">
                                    <label class="custom-control-label" for="clear_hmac_key">{{ __('general_content.delete_trans_key') }}</label>
                                </div>
                            @endif
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>{{ __('esignature.signing_mode') }}</label>
                            @foreach(['embedded', 'email'] as $mode)
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="signing_mode_{{ $mode }}" name="signing_mode" value="{{ $mode }}"
                                           class="custom-control-input"
                                           @checked(old('signing_mode', $setting->signing_mode ?? 'embedded') === $mode)>
                                    <label class="custom-control-label" for="signing_mode_{{ $mode }}">{{ __('esignature.signing_mode_' . $mode) }}</label>
                                </div>
                            @endforeach
                            <small class="form-text text-muted">{{ __('esignature.signing_mode_hint') }}</small>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   class="custom-control-input"
                                   @checked(old('is_active', $setting->is_active ?? false))>
                            <label class="custom-control-label" for="is_active">{{ __('esignature.is_active') }}</label>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <button type="button" id="btn-test" class="btn btn-outline-info" @if(! $setting) disabled @endif>
                            <i class="fas fa-plug"></i> {{ __('esignature.test_button') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('esignature.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div id="test-result"></div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">{{ __('esignature.consent_title') }}</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ __('esignature.consent_intro') }}</p>
                    <p><code class="text-break">{{ $redirect_uri }}</code></p>
                    @if($consent_url)
                        <a href="{{ $consent_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-warning">
                            <i class="fas fa-external-link-alt"></i> {{ __('esignature.consent_button') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">{{ __('esignature.webhook_title') }}</h3>
                </div>
                <div class="card-body">
                    @if($webhook_url)
                        <p class="mb-2">{{ __('esignature.webhook_https') }}</p>
                        <code class="text-break">{{ $webhook_url }}</code>
                    @else
                        <p class="mb-0 text-muted">{{ __('esignature.webhook_unreachable') }}</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('esignature.what_title') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="pl-3 mb-0">
                        <li>{{ __('esignature.what_1') }}</li>
                        <li>{{ __('esignature.what_2') }}</li>
                        <li>{{ __('esignature.what_3') }}</li>
                        <li>{{ __('esignature.what_4') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
<script>
(function () {
    const btn      = document.getElementById('btn-test');
    const resultEl = document.getElementById('test-result');
    if (! btn) return;

    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> ' + @json(__('esignature.test_running'));
        resultEl.innerHTML = '';

        try {
            const res = await fetch(@json(route('admin.integrations.esignature.test')), {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
            });
            const data = await res.json();

            const theme = data.ok ? 'success' : 'danger';
            const icon  = data.ok ? 'check-circle' : 'times-circle';
            const consent = data.consent_url
                ? `<div class="mt-2"><a href="${escape(data.consent_url)}" target="_blank" rel="noopener" class="btn btn-sm btn-warning">${escape(@json(__('esignature.consent_button')))}</a></div>`
                : '';

            resultEl.innerHTML = `
                <div class="alert alert-${theme}">
                    <i class="fas fa-${icon}"></i> <strong>${escape(data.message)}</strong>
                    ${consent}
                </div>
            `;
        } catch (e) {
            resultEl.innerHTML = `<div class="alert alert-danger">${escape(@json(__('esignature.test_network_error')))} ${escape(e.message)}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush
