@extends('adminlte::page')

@section('title', __('mail_settings.title'))

@section('content_header')
    <h1>
        <i class="fas fa-envelope mr-1"></i> {{ __('mail_settings.title') }}
        <small class="text-muted">{{ __('mail_settings.subtitle') }}</small>
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
                    <h3 class="card-title">{{ __('mail_settings.config') }}</h3>
                    <div class="card-tools">
                        @if($source === 'db')
                            <span class="badge badge-success" title="{{ __('mail_settings.source_db_hint') }}">DB</span>
                        @else
                            <span class="badge badge-warning" title="{{ __('mail_settings.source_env_hint') }}">.env</span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.integrations.mail.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="driver">{{ __('mail_settings.driver') }}</label>
                            <select id="driver" name="driver" class="form-control" required>
                                <option value="smtp" selected>SMTP</option>
                            </select>
                            <small class="form-text text-muted">
                                {{ __('mail_settings.driver_hint') }}
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="host">{{ __('mail_settings.host') }}</label>
                                    <input type="text" id="host" name="host" class="form-control" required
                                           value="{{ old('host', $setting->host ?? $resolved['host']) }}"
                                           placeholder="ssl0.ovh.net">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="port">{{ __('mail_settings.port') }}</label>
                                    <input type="number" id="port" name="port" class="form-control" required
                                           min="1" max="65535"
                                           value="{{ old('port', $setting->port ?? ($resolved['port'] ?: 587)) }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="encryption">{{ __('mail_settings.encryption') }}</label>
                                    <select id="encryption" name="encryption" class="form-control">
                                        <option value="">—</option>
                                        <option value="tls" @selected(old('encryption', $setting->encryption ?? $resolved['encryption']) === 'tls')>TLS</option>
                                        <option value="ssl" @selected(old('encryption', $setting->encryption ?? $resolved['encryption']) === 'ssl')>SSL</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">{{ __('mail_settings.username') }}</label>
                                    <input type="text" id="username" name="username" class="form-control"
                                           autocomplete="off"
                                           value="{{ old('username', $setting->username ?? $resolved['username']) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">
                                        {{ __('mail_settings.password') }}
                                        @if($has_password)
                                            <small class="text-success">({{ __('mail_settings.password_keep_hint') }})</small>
                                        @endif
                                    </label>
                                    <input type="password" id="password" name="password" class="form-control"
                                           autocomplete="new-password"
                                           placeholder="{{ $has_password ? '••••••••' : '' }}">
                                    <small class="form-text text-muted">
                                        {{ __('mail_settings.password_hint') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="from_address">{{ __('mail_settings.from_address') }}</label>
                                    <input type="email" id="from_address" name="from_address" class="form-control" required
                                           value="{{ old('from_address', $setting->from_address ?? $resolved['from_address']) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="from_name">{{ __('mail_settings.from_name') }}</label>
                                    <input type="text" id="from_name" name="from_name" class="form-control" required
                                           value="{{ old('from_name', $setting->from_name ?? $resolved['from_name']) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="timeout">{{ __('mail_settings.timeout') }}</label>
                                    <input type="number" id="timeout" name="timeout" class="form-control" required
                                           min="5" max="300"
                                           value="{{ old('timeout', $setting->timeout ?? 30) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch mt-4">
                                        <input type="hidden" name="reply_to_use_user" value="0">
                                        <input type="checkbox" id="reply_to_use_user" name="reply_to_use_user" value="1"
                                               class="custom-control-input"
                                               @checked(old('reply_to_use_user', $setting->reply_to_use_user ?? true))>
                                        <label class="custom-control-label" for="reply_to_use_user">
                                            {{ __('mail_settings.reply_to_use_user') }}
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        {{ __('mail_settings.reply_to_use_user_hint') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   class="custom-control-input"
                                   @checked(old('is_active', $setting->is_active ?? true))>
                            <label class="custom-control-label" for="is_active">{{ __('mail_settings.is_active') }}</label>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <button type="button" id="btn-test" class="btn btn-outline-info">
                            <i class="fas fa-plug"></i> {{ __('mail_settings.test_button') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('mail_settings.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">{{ __('mail_settings.what_uses_it') }}</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ __('mail_settings.used_by_intro') }}</p>
                    <ul class="mb-3">
                        <li>{{ __('mail_settings.used_by_documents') }}</li>
                        <li>{{ __('mail_settings.used_by_reports') }}</li>
                        <li>{{ __('mail_settings.used_by_notifications') }}</li>
                    </ul>
                    <p class="mb-0 text-muted">
                        {{ __('mail_settings.cache_hint') }}
                    </p>
                </div>
            </div>

            <div id="test-result" class="mt-3"></div>

            <a href="{{ route('admin.email-logs.index') }}" class="btn btn-outline-secondary btn-block mt-3">
                <i class="fas fa-history"></i> {{ __('mail_settings.see_logs') }}
            </a>
        </div>
    </div>
@stop

@push('js')
<script>
(function () {
    const btn      = document.getElementById('btn-test');
    const resultEl = document.getElementById('test-result');
    if (! btn) return;

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> {{ __('mail_settings.test_running') }}';
        resultEl.innerHTML = '';

        try {
            const res = await fetch(@json(route('admin.integrations.mail.test')), {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
            });
            const data = await res.json();

            const theme = data.ok ? 'success' : 'danger';
            const icon  = data.ok ? 'check-circle' : 'times-circle';

            resultEl.innerHTML = `
                <div class="alert alert-${theme}">
                    <i class="fas fa-${icon}"></i> <strong>${data.message}</strong>
                </div>
            `;
        } catch (e) {
            resultEl.innerHTML = `<div class="alert alert-danger">{{ __('mail_settings.test_network_error') }} ${e.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush
