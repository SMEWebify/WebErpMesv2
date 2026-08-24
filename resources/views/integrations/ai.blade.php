@extends('adminlte::page')

@section('title', 'Assistant IA')

@section('content_header')
    <h1>
        <i class="fas fa-robot mr-1"></i> Assistant IA
        <small class="text-muted">provider, clé API et modèle</small>
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
                    <h3 class="card-title">Configuration</h3>
                    <div class="card-tools">
                        @if($source === 'db')
                            <span class="badge badge-success" title="Lue depuis la base — modifiable ici.">DB</span>
                        @else
                            <span class="badge badge-warning" title="Lue depuis le fichier .env — cliquez sur « Importer depuis .env » pour basculer.">
                                .env
                            </span>
                        @endif
                        @if($has_key)
                            <span class="badge badge-info ml-1">Clé configurée</span>
                        @else
                            <span class="badge badge-danger ml-1">Clé manquante</span>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.integrations.ai.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="provider">Provider</label>
                            <select id="provider" name="provider" class="form-control" required>
                                @foreach($providers as $key => $info)
                                    <option value="{{ $key }}"
                                            data-default-model="{{ $info['default_model'] }}"
                                            @if(! $info['enabled']) disabled @endif
                                            @selected(($setting->provider ?? 'claude') === $key)>
                                        {{ $info['label'] }}
                                        @if(! $info['enabled']) — prochainement @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Seul Claude est branché pour l'instant. Les autres providers
                                seront ajoutés dans une prochaine version.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="api_key">
                                Clé API
                                @if($has_key)
                                    <small class="text-success">(déjà enregistrée — laissez vide pour ne pas changer)</small>
                                @endif
                            </label>
                            <input type="password"
                                   id="api_key"
                                   name="api_key"
                                   class="form-control"
                                   autocomplete="off"
                                   placeholder="{{ $has_key ? '••••••••••••••••' : 'sk-ant-...' }}">
                            <small class="form-text text-muted">
                                Chiffrée au repos avec la clé <code>APP_KEY</code> de Laravel.
                                Ne sera jamais renvoyée en clair dans une réponse HTTP.
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="model">Modèle</label>
                                    <input type="text"
                                           id="model"
                                           name="model"
                                           class="form-control"
                                           value="{{ old('model', $setting->model ?? $default_model) }}"
                                           placeholder="claude-haiku-4-5-20251001">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="max_tokens">Max tokens</label>
                                    <input type="number"
                                           id="max_tokens"
                                           name="max_tokens"
                                           class="form-control"
                                           min="256" max="8192"
                                           value="{{ old('max_tokens', $setting->max_tokens ?? 2048) }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="timeout_seconds">Timeout (s)</label>
                                    <input type="number"
                                           id="timeout_seconds"
                                           name="timeout_seconds"
                                           class="form-control"
                                           min="5" max="300"
                                           value="{{ old('timeout_seconds', $setting->timeout_seconds ?? 60) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="base_url">Base URL <small class="text-muted">(Ollama / endpoint auto-hébergé — laissez vide sinon)</small></label>
                            <input type="url"
                                   id="base_url"
                                   name="base_url"
                                   class="form-control"
                                   value="{{ old('base_url', $setting->base_url ?? '') }}"
                                   placeholder="https://api.anthropic.com">
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   class="custom-control-input"
                                   @checked(old('is_active', $setting->is_active ?? true))>
                            <label class="custom-control-label" for="is_active">Configuration active</label>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div>
                            @if($source === 'env' && $env_key_set)
                                <button type="submit"
                                        form="import-env-form"
                                        class="btn btn-outline-warning">
                                    <i class="fas fa-file-import"></i> Importer depuis .env
                                </button>
                            @endif

                            <button type="button" id="btn-test" class="btn btn-outline-info">
                                <i class="fas fa-plug"></i> Tester la connexion
                            </button>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>

            {{-- Formulaire séparé pour l'import (bouton ci-dessus le déclenche) --}}
            @if($source === 'env' && $env_key_set)
                <form id="import-env-form"
                      method="POST"
                      action="{{ route('admin.integrations.ai.import-env') }}"
                      class="d-none">
                    @csrf
                </form>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Où se sert la config ?</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Cette clé est utilisée par :</p>
                    <ul class="mb-3">
                        <li><strong>ChatWidget</strong> (bulle en bas à droite) — assistant ERP.</li>
                        <li><strong>get_daily_journal</strong> — génération du journal.</li>
                        <li><strong>UniversalQueryTool</strong> — requêtes ad hoc (top clients, retards…).</li>
                    </ul>
                    <p class="mb-2 text-muted">
                        La modification est prise en compte au bout de <strong>60 secondes</strong>
                        (cache interne), ou immédiatement après un <em>Enregistrer</em>.
                    </p>
                </div>
            </div>

            <div id="test-result" class="mt-3"></div>
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
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Test en cours…';
        resultEl.innerHTML = '';

        try {
            const res = await fetch(@json(route('admin.integrations.ai.test')), {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
            });
            const data = await res.json();

            const theme = data.ok ? 'success' : 'danger';
            const icon  = data.ok ? 'check-circle' : 'times-circle';
            const parts = [];
            parts.push(`<strong>${data.message}</strong>`);
            if (data.ok) {
                if (data.model)  parts.push(`Modèle : <code>${data.model}</code>`);
                if (data.reply)  parts.push(`Réponse : <em>${data.reply}</em>`);
                if (data.source) parts.push(`Source : <code>${data.source}</code>`);
            }

            resultEl.innerHTML = `
                <div class="alert alert-${theme}">
                    <i class="fas fa-${icon}"></i>
                    ${parts.join(' — ')}
                </div>
            `;
        } catch (e) {
            resultEl.innerHTML = `<div class="alert alert-danger">Erreur réseau : ${e.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush
