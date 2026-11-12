@extends('adminlte::page')

@section('title', __('nest2bend.title'))

@section('content_header')
    <h1>
        <i class="fas fa-bezier-curve me-1"></i> {{ __('nest2bend.title') }}
        <small class="text-muted">{{ __('nest2bend.subtitle') }}</small>
    </h1>
@stop

@php
    $badgeTheme = [
        'none'    => 'secondary',
        'unknown' => 'warning',
        'invalid' => 'danger',
        'expired' => 'danger',
        'active'  => 'success',
    ][$state['status']] ?? 'secondary';
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
            <div class="card card-outline card-{{ $state['status'] === 'invalid' ? 'danger' : 'primary' }} mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('nest2bend.config') }}</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-{{ $badgeTheme }}">{{ __('nest2bend.badge.' . $state['status']) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.integrations.nest2bend.update') }}" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if($state['status'] === 'invalid')
                            <div class="alert alert-danger"><i class="fas fa-times-circle me-1"></i>{{ __('nest2bend.state.invalid') }}</div>
                        @elseif($state['expired'])
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>{{ __('nest2bend.state.expired') }}
                                @if($state['app_url'])
                                    <a href="{{ $state['app_url'] }}" target="_blank" rel="noopener" class="alert-link ms-1">{{ __('nest2bend.button.renew') }}</a>
                                @endif
                            </div>
                        @elseif($state['warning'])
                            <div class="alert alert-warning">
                                <i class="fas fa-hourglass-half me-1"></i>{{ __('nest2bend.state.warning', ['etat' => __('nest2bend.etats.' . $state['etat']), 'echeance' => $state['echeance']?->format('d/m/Y') ?? '—']) }}
                            </div>
                        @elseif($state['status'] === 'unknown')
                            <div class="alert alert-warning"><i class="fas fa-question-circle me-1"></i>{{ __('nest2bend.state.unknown') }}</div>
                        @endif

                        <div class="mb-3">
                            <label for="base_url">{{ __('nest2bend.base_url') }}</label>
                            <input type="url" id="base_url" name="base_url" class="form-control" required
                                   value="{{ old('base_url', $setting->base_url ?? '') }}"
                                   placeholder="https://app.nest2bend.fr">
                            <small class="form-text text-muted">{{ __('nest2bend.base_url_hint') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="api_key">
                                {{ __('nest2bend.api_key') }}
                                @if($masked_key)
                                    <small class="text-success">(<code>{{ $masked_key }}</code> — {{ __('nest2bend.api_key_keep') }})</small>
                                @endif
                            </label>
                            {{-- La clé n'est jamais renvoyée : le champ reste vide. --}}
                            <input type="password" id="api_key" name="api_key" class="form-control font-monospace"
                                   autocomplete="new-password" spellcheck="false"
                                   placeholder="{{ $masked_key ?? 'n2b_…' }}">
                            <small class="form-text text-muted">{{ __('nest2bend.api_key_hint') }}</small>
                            @if($masked_key)
                                <div class="form-check mt-1">
                                    <input type="checkbox" id="clear_key" name="clear_key" value="1" class="form-check-input">
                                    <label class="form-check-label" for="clear_key">{{ __('nest2bend.clear_key') }}</label>
                                </div>
                            @endif
                        </div>

                        @if($state['configured'])
                            <dl class="row mb-0">
                                @if($state['societe'])
                                    <dt class="col-sm-4">{{ __('nest2bend.societe') }}</dt>
                                    <dd class="col-sm-8">{{ $state['societe'] }}</dd>
                                @endif
                                @if($state['etat'])
                                    <dt class="col-sm-4">{{ __('nest2bend.etat') }}</dt>
                                    <dd class="col-sm-8"><code>{{ $state['etat'] }}</code></dd>
                                @endif
                                @if($state['echeance'])
                                    <dt class="col-sm-4">{{ __('nest2bend.echeance') }}</dt>
                                    <dd class="col-sm-8">{{ $state['echeance']->format('d/m/Y') }}</dd>
                                @endif
                                @if($state['modules'])
                                    <dt class="col-sm-4">{{ __('nest2bend.modules') }}</dt>
                                    <dd class="col-sm-8">
                                        @foreach($state['modules'] as $module)
                                            <span class="badge text-bg-secondary">{{ $module }}</span>
                                        @endforeach
                                    </dd>
                                @endif
                                @if($state['presses_max'])
                                    <dt class="col-sm-4">{{ __('nest2bend.presses_max') }}</dt>
                                    <dd class="col-sm-8">{{ $state['presses_max'] }}</dd>
                                @endif
                                <dt class="col-sm-4">{{ __('nest2bend.checked_at') }}</dt>
                                <dd class="col-sm-8">
                                    @if($state['checked_at'])
                                        <span title="{{ $state['checked_at'] }}">{{ $state['checked_at']->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">{{ __('nest2bend.never_checked') }}</span>
                                    @endif
                                </dd>
                                @if($state['last_error'])
                                    <dt class="col-sm-4">{{ __('nest2bend.last_error') }}</dt>
                                    <dd class="col-sm-8 text-danger">{{ $state['last_error'] }}</dd>
                                @endif
                            </dl>
                        @endif
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <button type="button" id="btn-test" class="btn btn-outline-info" @if(! $state['configured']) disabled @endif>
                            <i class="fas fa-plug"></i> {{ __('nest2bend.test_button') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('nest2bend.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div id="test-result"></div>

            <div class="card card-outline card-warning mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('nest2bend.origin_title') }}</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ __('nest2bend.origin_intro') }}</p>
                    <code class="text-break">{{ $erp_origin }}</code>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('nest2bend.what_title') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="ps-3 mb-0">
                        <li>{{ __('nest2bend.what_1') }}</li>
                        <li>{{ __('nest2bend.what_2') }}</li>
                        <li>{{ __('nest2bend.what_3') }}</li>
                        <li>{{ __('nest2bend.what_4') }}</li>
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
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> ' + @json(__('nest2bend.test_running'));
        resultEl.innerHTML = '';

        try {
            const res = await fetch(@json(route('admin.integrations.nest2bend.test')), {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
            });
            const data = await res.json();

            const theme = data.ok ? 'success' : 'danger';
            const icon  = data.ok ? 'check-circle' : 'times-circle';
            const warning = data.warning ? `<div class="mt-1">${escape(data.warning)}</div>` : '';

            resultEl.innerHTML = `
                <div class="alert alert-${theme}">
                    <i class="fas fa-${icon}"></i> <strong>${escape(data.message)}</strong>
                    ${warning}
                </div>
            `;
        } catch (e) {
            resultEl.innerHTML = `<div class="alert alert-danger">${escape(@json(__('nest2bend.test_network_error')))} ${escape(e.message)}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush
