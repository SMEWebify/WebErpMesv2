@extends('adminlte::page')

@section('title', __('configurators.admin.title'))

@section('content_header')
    <h1>
        <i class="fas fa-sliders-h me-1"></i> {{ __('configurators.admin.title') }}
        <small class="text-muted">{{ __('configurators.admin.subtitle') }}</small>
    </h1>
@stop

@php
    $form = $editing ?? new \App\Models\Integrations\ExternalConfigurator([
        'display' => 'iframe', 'price_mode' => 'gross', 'document_types' => ['quote', 'order'], 'active' => true,
    ]);
    $selectedTypes = old('document_types', $form->document_types ?? []);
    $selectedFamilies = array_map('intval', old('families', $form->families ?? []));
@endphp

@section('content')

    @if(session('success'))
        <x-adminlte-alert theme="success" dismissable>{{ session('success') }}</x-adminlte-alert>
    @endif
    @if(session('warning'))
        <x-adminlte-alert theme="warning" dismissable>{{ session('warning') }}</x-adminlte-alert>
    @endif
    @if(! $enabled)
        <x-adminlte-alert theme="warning">{{ __('configurators.admin.disabled_env') }}</x-adminlte-alert>
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
        <div class="col-lg-7">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('configurators.admin.list') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.integrations.configurators.index') }}" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> {{ __('configurators.admin.new') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('configurators.admin.label') }}</th>
                                <th>{{ __('configurators.admin.document_types') }}</th>
                                <th>{{ __('configurators.admin.last_check') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($configurators as $configurator)
                                @php
                                    $badge = $configurator->last_error ? ['danger', 'badge_error']
                                        : ($configurator->active && $configurator->hasKey() ? ['success', 'badge_active'] : ['secondary', 'badge_inactive']);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $configurator->label }}</strong>
                                        <span class="badge text-bg-{{ $badge[0] }} ms-1">{{ __('configurators.admin.' . $badge[1]) }}</span>
                                        <div class="small text-body-secondary">{{ $configurator->code }} · {{ $configurator->base_url }}</div>
                                        @if($configurator->last_error)
                                            <div class="small text-danger">{{ $configurator->last_error }}</div>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @foreach($configurator->document_types ?? [] as $type)
                                            {{ __('configurators.admin.' . $type) }}@if(! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td class="small">
                                        {{ $configurator->last_check_at?->diffForHumans() ?? __('configurators.admin.never_checked') }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-configurator-test"
                                                data-url="{{ route('admin.integrations.configurators.test', $configurator) }}">
                                            <i class="fas fa-plug"></i> {{ __('configurators.admin.test') }}
                                        </button>
                                        <a href="{{ route('admin.integrations.configurators.index', ['edit' => $configurator->id]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-3">{{ __('configurators.admin.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="small text-body-secondary">
                {{ __('configurators.admin.origin_hint') }} <code>{{ $erp_origin }}</code>
            </p>
        </div>

        <div class="col-lg-5">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $editing ? __('configurators.admin.edit', ['label' => $editing->label]) : __('configurators.admin.new') }}
                    </h3>
                </div>
                <form method="POST" autocomplete="off"
                      action="{{ $editing ? route('admin.integrations.configurators.update', $editing) : route('admin.integrations.configurators.store') }}">
                    @csrf
                    @if($editing) @method('PUT') @endif
                    <div class="card-body">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="code" class="form-label">{{ __('configurators.admin.code') }}</label>
                                <input type="text" id="code" name="code" class="form-control" required maxlength="50" value="{{ old('code', $form->code) }}">
                            </div>
                            <div class="mb-3 col-md-8">
                                <label for="label" class="form-label">{{ __('configurators.admin.label') }}</label>
                                <input type="text" id="label" name="label" class="form-control" required maxlength="255" value="{{ old('label', $form->label) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="base_url" class="form-label">{{ __('configurators.admin.base_url') }}</label>
                            <input type="url" id="base_url" name="base_url" class="form-control" required value="{{ old('base_url', $form->base_url) }}" placeholder="https://configurateur.exemple.com">
                            <small class="form-text text-body-secondary">{{ __('configurators.admin.base_url_hint') }}</small>
                        </div>
                        <div class="mb-3">
                            <label for="api_key" class="form-label">{{ __('configurators.admin.api_key') }}</label>
                            <input type="password" id="api_key" name="api_key" class="form-control" autocomplete="new-password"
                                   placeholder="{{ $form->maskedKey() ?? '' }}">
                            <small class="form-text text-body-secondary">{{ __('configurators.admin.api_key_hint') }}</small>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="display" class="form-label">{{ __('configurators.admin.display') }}</label>
                                <select id="display" name="display" class="form-select">
                                    @foreach(['iframe', 'tab'] as $display)
                                        <option value="{{ $display }}" @selected(old('display', $form->display) === $display)>{{ __('configurators.admin.display_' . $display) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label d-block">{{ __('configurators.admin.document_types') }}</label>
                                @foreach(['quote', 'order'] as $type)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="type_{{ $type }}" name="document_types[]" value="{{ $type }}" @checked(in_array($type, $selectedTypes, true))>
                                        <label class="form-check-label" for="type_{{ $type }}">{{ __('configurators.admin.' . $type) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="price_mode" class="form-label">{{ __('configurators.admin.price_mode') }}</label>
                            <select id="price_mode" name="price_mode" class="form-select">
                                @foreach(['gross', 'net'] as $mode)
                                    <option value="{{ $mode }}" @selected(old('price_mode', $form->price_mode) === $mode)>{{ __('configurators.admin.price_' . $mode) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="families" class="form-label">{{ __('configurators.admin.families') }}</label>
                            <select id="families" name="families[]" class="form-select" multiple size="5">
                                @foreach($families as $family)
                                    <option value="{{ $family->id }}" @selected(in_array($family->id, $selectedFamilies, true))>{{ $family->code }} — {{ $family->label }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-body-secondary">{{ __('configurators.admin.families_hint') }}</small>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="active" value="0">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" @checked(old('active', $form->active))>
                            <label class="form-check-label" for="active">{{ __('configurators.admin.active') }}</label>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        @if($editing)
                            <a href="{{ route('admin.integrations.configurators.index') }}" class="btn btn-outline-secondary">{{ __('configurators.admin.cancel') }}</a>
                        @endif
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('configurators.admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Bouton « Tester » : GET /api/v1/ping du configurateur, résultat affiché sous forme de message.
        document.querySelectorAll('.js-configurator-test').forEach(function (button) {
            button.addEventListener('click', async function () {
                button.disabled = true;
                try {
                    const res = await fetch(button.dataset.url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    });
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || res.statusText);
                    window.location.reload();
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
@stop
