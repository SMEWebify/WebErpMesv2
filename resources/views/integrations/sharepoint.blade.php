@extends('adminlte::page')

@section('title', __('sharepoint.title'))

@section('content_header')
    <h1>
        <i class="fab fa-microsoft me-1"></i> {{ __('sharepoint.title') }}
        <small class="text-muted">{{ __('sharepoint.subtitle') }}</small>
    </h1>
@stop

@php
    $status = $setting->status ?? 'none';
    $badgeTheme = ['none' => 'secondary', 'ok' => 'success', 'error' => 'danger'][$status] ?? 'secondary';
@endphp

@section('content')

    @if(session('success'))
        <x-adminlte-alert theme="success" dismissable>{{ session('success') }}</x-adminlte-alert>
    @endif
    @if(session('warning'))
        <x-adminlte-alert theme="warning" dismissable>{{ session('warning') }}</x-adminlte-alert>
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
            <div class="card card-outline card-{{ $status === 'error' ? 'danger' : 'primary' }} mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('sharepoint.config') }}</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-{{ $badgeTheme }}">{{ __('sharepoint.badge.' . $status) }}</span>
                        @if($setting && ! $setting->is_active)
                            <span class="badge text-bg-secondary">{{ __('sharepoint.badge.inactive') }}</span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.integrations.sharepoint.update') }}" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if($status === 'error' && $setting?->last_error)
                            <div class="alert alert-danger"><i class="fas fa-times-circle me-1"></i>{{ $setting->last_error }}</div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tenant_id" class="form-label">{{ __('sharepoint.tenant_id') }}</label>
                                <input type="text" id="tenant_id" name="tenant_id" class="form-control font-monospace" required
                                       value="{{ old('tenant_id', $setting->tenant_id ?? '') }}" spellcheck="false">
                                <small class="form-text text-muted">{{ __('sharepoint.tenant_id_hint') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="client_id" class="form-label">{{ __('sharepoint.client_id') }}</label>
                                <input type="text" id="client_id" name="client_id" class="form-control font-monospace" required
                                       value="{{ old('client_id', $setting->client_id ?? '') }}" spellcheck="false">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="client_secret" class="form-label">
                                {{ __('sharepoint.client_secret') }}
                                @if($has_secret)
                                    <small class="text-success">({{ __('sharepoint.secret_keep') }})</small>
                                @endif
                            </label>
                            {{-- Le secret n'est jamais renvoyé : le champ reste vide. --}}
                            <input type="password" id="client_secret" name="client_secret" class="form-control font-monospace"
                                   autocomplete="new-password" spellcheck="false" @if(! $has_secret) required @endif
                                   placeholder="{{ $has_secret ? '••••••••' : '' }}">
                            <small class="form-text text-muted">{{ __('sharepoint.client_secret_hint') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="site_url" class="form-label">{{ __('sharepoint.site_url') }}</label>
                            <input type="url" id="site_url" name="site_url" class="form-control" required
                                   value="{{ old('site_url', $setting->site_url ?? '') }}"
                                   placeholder="https://societe.sharepoint.com/sites/ERP">
                            <small class="form-text text-muted">{{ __('sharepoint.site_url_hint') }}</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="library" class="form-label">{{ __('sharepoint.library') }}</label>
                                <input type="text" id="library" name="library" class="form-control"
                                       value="{{ old('library', $setting->library ?? '') }}">
                                <small class="form-text text-muted">{{ __('sharepoint.library_hint') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="root_folder" class="form-label">{{ __('sharepoint.root_folder') }}</label>
                                <input type="text" id="root_folder" name="root_folder" class="form-control"
                                       value="{{ old('root_folder', $setting->root_folder ?? '') }}"
                                       placeholder="{{ \App\Models\Integrations\SharePointSetting::DEFAULT_ROOT_FOLDER }}">
                                <small class="form-text text-muted">{{ __('sharepoint.root_folder_hint') }}</small>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                                   @checked(old('is_active', $setting->is_active ?? false))>
                            <label class="form-check-label" for="is_active">{{ __('sharepoint.is_active') }}</label>
                            <div><small class="form-text text-muted">{{ __('sharepoint.is_active_hint') }}</small></div>
                        </div>

                        @if($setting)
                            <dl class="row mb-0">
                                <dt class="col-sm-4">{{ __('sharepoint.checked_at') }}</dt>
                                <dd class="col-sm-8">
                                    @if($setting->checked_at)
                                        <span title="{{ $setting->checked_at }}">{{ $setting->checked_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">{{ __('sharepoint.never_checked') }}</span>
                                    @endif
                                </dd>
                                @if($setting->drive_web_url)
                                    <dt class="col-sm-4">{{ __('sharepoint.library') }}</dt>
                                    <dd class="col-sm-8">
                                        <a href="{{ $setting->drive_web_url }}" target="_blank" rel="noopener">
                                            {{ __('sharepoint.open_library') }} <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </dd>
                                @endif
                            </dl>
                        @endif
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <button type="button" id="btn-test" class="btn btn-outline-info" @if(! $setting?->isComplete()) disabled @endif>
                            <i class="fas fa-plug"></i> {{ __('sharepoint.test_button') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('sharepoint.save') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('sharepoint.stats_title') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        @foreach(['synced' => 'success', 'pending' => 'info', 'failed' => 'danger', 'skipped' => 'secondary'] as $key => $theme)
                            <div class="col-6 col-md-3">
                                <div class="fs-4 fw-bold text-{{ $theme }}">{{ $stats[$key] }}</div>
                                <div class="text-muted small">{{ __('sharepoint.stats.' . $key) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-muted small mb-2"><i class="fas fa-info-circle me-1"></i>{{ __('sharepoint.queue_hint') }}</p>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.integrations.sharepoint.backfill') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" @if(! $setting?->isUsable()) disabled @endif
                                    title="{{ __('sharepoint.backfill_hint') }}">
                                <i class="fas fa-cloud-upload-alt"></i> {{ __('sharepoint.backfill_button') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.integrations.sharepoint.retry') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger" @if($stats['failed'] === 0) disabled @endif>
                                <i class="fas fa-redo"></i> {{ __('sharepoint.retry_button') }}
                            </button>
                        </form>
                    </div>
                    <small class="form-text text-muted">{{ __('sharepoint.backfill_hint') }}</small>

                    @if($failures->isNotEmpty())
                        <h6 class="mt-3">{{ __('sharepoint.failures_title') }}</h6>
                        <ul class="list-unstyled small mb-0">
                            @foreach($failures as $failure)
                                <li class="text-danger">
                                    <span class="text-muted">{{ $failure->updated_at?->format('d/m/Y H:i') }} ·
                                        {{ \App\Services\Files\FileableRegistry::aliasFor($failure->fileable_type) ?? class_basename($failure->fileable_type) }} #{{ $failure->fileable_id }} ·
                                        {{ __('sharepoint.file') }} #{{ $failure->file_id }}</span>
                                    — {{ $failure->last_error }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div id="test-result"></div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('sharepoint.what_title') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="ps-3 mb-0">
                        <li>{{ __('sharepoint.what_1') }}</li>
                        <li>{{ __('sharepoint.what_2') }}</li>
                        <li>{{ __('sharepoint.what_3') }}</li>
                        <li>{{ __('sharepoint.what_4') }}</li>
                    </ol>
                </div>
            </div>

            <div class="card card-outline card-warning mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('sharepoint.setup_title') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="ps-3 mb-0">
                        <li>{{ __('sharepoint.setup_1') }}</li>
                        <li>{{ __('sharepoint.setup_2') }}</li>
                        <li>{{ __('sharepoint.setup_3') }}</li>
                        <li>{{ __('sharepoint.setup_4') }}</li>
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
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> ' + @json(__('sharepoint.test_running'));
        resultEl.innerHTML = '';

        try {
            const res = await fetch(@json(route('admin.integrations.sharepoint.test')), {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
            });
            const data = await res.json();

            const theme = data.ok ? 'success' : 'danger';
            const icon  = data.ok ? 'check-circle' : 'times-circle';

            resultEl.innerHTML = `<div class="alert alert-${theme}"><i class="fas fa-${icon}"></i> <strong>${escape(data.message)}</strong></div>`;
        } catch (e) {
            resultEl.innerHTML = `<div class="alert alert-danger">${escape(@json(__('sharepoint.test_network_error')))} ${escape(e.message)}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush
