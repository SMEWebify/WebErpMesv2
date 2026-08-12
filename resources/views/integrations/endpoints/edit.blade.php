@extends('adminlte::page')

@section('title', $isNew ? 'Nouvel endpoint' : 'Endpoint · ' . $endpoint->name)

@section('content_header')
    <h1>
        {{ $isNew ? 'Nouvel endpoint' : $endpoint->name }}
        <small class="text-muted">
            @if(! $isNew)
                <code>{{ $endpoint->system_code }}</code> · {{ $endpoint->direction }}
            @endif
        </small>
    </h1>
    <a href="{{ route('admin.integrations.endpoints.index') }}" class="btn btn-link p-0">
        <i class="fas fa-arrow-left"></i> Retour à la liste
    </a>
@stop

@section('content')
    @if(session('success'))
        <x-adminlte-alert theme="success" title="{{ __('general_content.success_trans_key') }}">
            {{ session('success') }}
        </x-adminlte-alert>
    @endif

    @if(session('error'))
        <x-adminlte-alert theme="danger" title="Erreur">
            {{ session('error') }}
        </x-adminlte-alert>
    @endif

    @if(session('revealed_secrets') && count(session('revealed_secrets')) > 0)
        <x-adminlte-card title="🔑 Secrets à copier MAINTENANT" theme="warning" theme-mode="outline">
            <div class="alert alert-warning mb-3 py-2">
                <strong>Ces valeurs ne seront plus jamais réaffichées.</strong> Copie-les maintenant
                dans le système partenaire, puis quitte cette page. Un rafraîchissement les masque.
            </div>

            @foreach(session('revealed_secrets') as $key => $value)
                <div class="mb-3">
                    <label class="font-weight-bold">{{ $key === 'bearer_token' ? 'Bearer token' : 'Secret HMAC' }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" readonly value="{{ $value }}"
                               id="reveal-{{ $key }}"
                               onclick="this.select()">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary" onclick="copySecret('reveal-{{ $key }}', this)">
                                <i class="fas fa-copy"></i> Copier
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            <script>
                function copySecret(elId, btn) {
                    var el = document.getElementById(elId);
                    el.select();
                    el.setSelectionRange(0, 99999);
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(el.value).then(function () {
                            var orig = btn.innerHTML;
                            btn.innerHTML = '<i class="fas fa-check"></i> Copié';
                            setTimeout(function () { btn.innerHTML = orig; }, 1500);
                        });
                    } else {
                        document.execCommand('copy');
                    }
                }
            </script>
        </x-adminlte-card>
    @endif

    @if($errors->any())
        <x-adminlte-alert theme="danger" title="Erreurs de validation">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    <form method="POST"
          action="{{ $isNew ? route('admin.integrations.endpoints.store') : route('admin.integrations.endpoints.update', $endpoint) }}">
        @csrf
        @unless($isNew) @method('PUT') @endunless

        <x-adminlte-card title="Identité" theme="primary" theme-mode="outline">
            <div class="row">
                <div class="col-md-6">
                    <x-adminlte-input name="name" label="Nom (affichage)"
                                      :value="old('name', $endpoint->name)" required/>
                </div>
                <div class="col-md-3">
                    <x-adminlte-input name="system_code" label="Code système (slug)"
                                      :value="old('system_code', $endpoint->system_code)"
                                      placeholder="n2p, shopify, ..." required/>
                </div>
                <div class="col-md-3">
                    <label>Direction</label>
                    <select name="direction" class="form-control" required @if(! $isNew) disabled @endif>
                        <option value="outbound" @selected(old('direction', $endpoint->direction) === 'outbound')>Sortant (ERP → externe)</option>
                        <option value="inbound"  @selected(old('direction', $endpoint->direction) === 'inbound')>Entrant (externe → ERP)</option>
                    </select>
                    @if(! $isNew)
                        <input type="hidden" name="direction" value="{{ $endpoint->direction }}">
                    @endif
                </div>
            </div>
        </x-adminlte-card>

        <x-adminlte-card title="Transport" theme="primary" theme-mode="outline">
            <div class="row">
                <div class="col-md-8">
                    <x-adminlte-input name="url" label="URL cible (sortant uniquement)"
                                      :value="old('url', $endpoint->url)"
                                      placeholder="https://partenaire.example.com"/>
                    @if($endpoint->direction === 'inbound' && ! $isNew)
                        <small class="text-muted">
                            URL exposée : <code>{{ url('/api/integrations/' . $endpoint->system_code . '/inbound') }}</code>
                        </small>
                    @endif
                </div>
                <div class="col-md-4">
                    <x-adminlte-input-switch name="verify_ssl" label="Vérifier le certificat SSL"
                                             data-on-text="Oui" data-off-text="Non"
                                             :checked="old('verify_ssl', $endpoint->verify_ssl ?? true)"/>
                </div>
            </div>
        </x-adminlte-card>

        <x-adminlte-card title="Authentification" theme="primary" theme-mode="outline">
            <div class="row">
                <div class="col-md-4">
                    <label>Méthode</label>
                    <select name="auth_method" class="form-control" required>
                        <option value="none"         @selected(old('auth_method', $endpoint->auth_method) === 'none')>Aucune (dev)</option>
                        <option value="bearer"      @selected(old('auth_method', $endpoint->auth_method) === 'bearer')>Bearer</option>
                        <option value="hmac"        @selected(old('auth_method', $endpoint->auth_method) === 'hmac')>HMAC-SHA256</option>
                        <option value="bearer_hmac" @selected(old('auth_method', $endpoint->auth_method) === 'bearer_hmac')>Bearer + HMAC</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Bearer token</label>
                    <input type="password" name="bearer_token" class="form-control"
                           value="{{ old('bearer_token', $endpoint->bearer_token ? '__keep__' : '') }}"
                           autocomplete="off"
                           placeholder="-">
                    <small class="text-muted">Laisser <code>__keep__</code> pour conserver l'actuel.</small>
                </div>
                <div class="col-md-4">
                    <label>Secret HMAC</label>
                    <input type="password" name="hmac_secret" class="form-control"
                           value="{{ old('hmac_secret', $endpoint->hmac_secret ? '__keep__' : '') }}"
                           autocomplete="off"
                           placeholder="-">
                    <small class="text-muted">Laisser <code>__keep__</code> pour conserver l'actuel.</small>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4">
                    <x-adminlte-input name="hmac_header" label="Header signature"
                                      :value="old('hmac_header', $endpoint->hmac_header ?? 'X-Signature-256')"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="timestamp_header" label="Header timestamp"
                                      :value="old('timestamp_header', $endpoint->timestamp_header ?? 'X-Timestamp')"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="timestamp_tolerance_s" label="Tolérance timestamp (s)" type="number"
                                      min="30" max="3600"
                                      :value="old('timestamp_tolerance_s', $endpoint->timestamp_tolerance_s ?? 300)"/>
                </div>
            </div>

            {{-- Régénération secrets + test de connexion : boutons sortis DU form
                 principal, un cran plus bas, parce que HTML interdit les <form>
                 imbriqués — sinon le clic soumettait l'update sans jamais atteindre
                 le contrôleur regenerate/test. Voir bloc "Actions rapides". --}}
        </x-adminlte-card>

        @if(! $isNew && $endpoint->direction === 'inbound')
            <x-adminlte-card title="Tester depuis le partenaire (curl)" theme="info" theme-mode="outline">
                <p class="text-muted small mb-2">
                    Copie ce snippet, remplace <code>&lt;HMAC_SECRET&gt;</code> par la valeur (visible une seule fois à la génération),
                    et exécute-le depuis la machine partenaire pour valider le tunnel HMAC.
                </p>
@php
    $inboundUrl = url('/api/integrations/' . $endpoint->system_code . '/inbound');
    $curl = <<<BASH
BODY='{"event_id":"11111111-1111-1111-1111-111111111111","event_type":"ping","occurred_at":"2026-08-10T10:00:00Z","source":"partner","data":{}}'
TS=\$(date +%s)
SIG=\$(printf "%s" "\${TS}.\${BODY}" | openssl dgst -sha256 -hmac "<HMAC_SECRET>" -hex | awk '{print \$2}')
curl -sS -X POST {$inboundUrl} \\
  -H "Content-Type: application/json" \\
  -H "{$endpoint->timestamp_header}: \${TS}" \\
  -H "{$endpoint->hmac_header}: \${SIG}" \\
  -d "\${BODY}"
BASH;
@endphp
                <pre class="bg-light p-3 rounded"><code>{{ $curl }}</code></pre>
                <small class="text-muted">
                    Résultat attendu : <code>200 {"status":"ignored", "reason":"event_type_not_subscribed"}</code>
                    (le tunnel HMAC est validé, mais <code>ping</code> n'a pas de handler - normal).
                </small>
            </x-adminlte-card>
        @endif

        @if($endpoint->system_code === 'n2p' && old('direction', $endpoint->direction) === 'outbound')
            @php
                $meta = is_array($endpoint->metadata) ? $endpoint->metadata : [];
                $statusOptions = [
                    'OPEN'              => 'Ouvert (1)',
                    'IN_PROGRESS'       => 'En cours (2)',
                    'DELIVERED'         => 'Livré (3)',
                    'PARTLY_DELIVERED'  => 'Partiellement livré (4)',
                    'STOPPED'           => 'Suspendu (5)',
                    'CANCELED'          => 'Annulé (6)',
                ];
                $metaStatusFrom = old('metadata.status_transition_from', $meta['status_transition_from'] ?? 'OPEN');
                $metaStatusTo   = old('metadata.status_transition_to',   $meta['status_transition_to'] ?? 'IN_PROGRESS');
                $metaSendTasks  = old('metadata.send_tasks',        $meta['send_tasks'] ?? true);
                $metaJobStatus  = old('metadata.job_status_on_send', $meta['job_status_on_send'] ?? 'released');
                $metaPriority   = old('metadata.default_priority',  $meta['default_priority'] ?? 3);
            @endphp
            <x-adminlte-card title="Règles métier N2P (push OF)" theme="warning" theme-mode="outline">
                <p class="text-muted small mb-3">
                    Détermine <strong>quand</strong> et <strong>avec quel niveau de détail</strong> une commande est
                    poussée vers Nest2Prod. Le push se déclenche à la transition
                    <em>{{ $statusOptions[$metaStatusFrom] ?? $metaStatusFrom }} → {{ $statusOptions[$metaStatusTo] ?? $metaStatusTo }}</em>.
                </p>

                <div class="row">
                    <div class="col-md-6">
                        <label>Transition — statut de départ</label>
                        <select name="metadata[status_transition_from]" class="form-control">
                            @foreach($statusOptions as $val => $lbl)
                                <option value="{{ $val }}" @selected($metaStatusFrom === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Transition — statut d'arrivée</label>
                        <select name="metadata[status_transition_to]" class="form-control">
                            @foreach($statusOptions as $val => $lbl)
                                <option value="{{ $val }}" @selected($metaStatusTo === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <x-adminlte-input-switch name="metadata[send_tasks]" label="Inclure les tâches dans le payload"
                                                 data-on-text="Oui" data-off-text="Non"
                                                 :checked="$metaSendTasks"/>
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-input name="metadata[job_status_on_send]" label="Statut job à l'envoi"
                                          :value="$metaJobStatus"
                                          placeholder="released"/>
                        <small class="text-muted">Statut initial du job côté Nest2Prod (ex : <code>released</code>, <code>scheduled</code>).</small>
                    </div>
                    <div class="col-md-4">
                        <x-adminlte-input name="metadata[default_priority]" label="Priorité par défaut (1..5)" type="number"
                                          min="1" max="5"
                                          :value="$metaPriority"/>
                    </div>
                </div>
            </x-adminlte-card>
        @endif

        <x-adminlte-card title="Souscriptions & fiabilité" theme="primary" theme-mode="outline">
            @php
                $direction = old('direction', $endpoint->direction);
                $selectedEvents = collect(old('events', is_array($endpoint->events) ? $endpoint->events : []))
                    ->map(fn ($e) => (string) $e)->all();
            @endphp

            @if($direction === 'inbound')
                <div class="row">
                    <div class="col-md-12">
                        <label class="d-block">Events souscrits (entrants)</label>
                        <small class="text-muted d-block mb-2">
                            Aucune case cochée = souscrit à <strong>tout</strong> (comportement par défaut).
                            Cocher au moins un event = filtre strict (les autres sont renvoyés <code>202 ignored</code>).
                        </small>

                        @foreach($eventCatalog as $groupLabel => $events)
                            <div class="border rounded p-2 mb-2">
                                <div class="font-weight-bold small text-muted mb-1">{{ $groupLabel }}</div>
                                <div class="row">
                                    @foreach($events as $eventType => $eventLabel)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       name="events[]"
                                                       value="{{ $eventType }}"
                                                       id="ev-{{ Str::slug($eventType, '_') }}"
                                                       @checked(in_array($eventType, $selectedEvents, true))>
                                                <label class="form-check-label" for="ev-{{ Str::slug($eventType, '_') }}">
                                                    {{ $eventLabel }}
                                                    <br><code class="small text-muted">{{ $eventType }}</code>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Events legacy custom (non catalogués) : préservés en hidden pour ne pas
                             les perdre au save d'un endpoint qui souscrit à un event non standard. --}}
                        @php
                            $catalogKeys = collect($eventCatalog)->flatMap(fn ($g) => array_keys($g))->all();
                            $extras = array_values(array_diff($selectedEvents, $catalogKeys));
                        @endphp
                        @if(count($extras) > 0)
                            <div class="alert alert-info py-2 small">
                                <strong>Events custom conservés :</strong>
                                @foreach($extras as $extra)
                                    <code class="mr-1">{{ $extra }}</code>
                                    <input type="hidden" name="events[]" value="{{ $extra }}">
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-secondary py-2 small mb-0">
                    <i class="fas fa-info-circle"></i>
                    Le champ "events souscrits" ne s'applique qu'aux endpoints <strong>entrants</strong>.
                    Les events sortants (<code>job.pushed</code>, <code>sheet_lot.pushed</code>) sont câblés côté code.
                </div>
                {{-- On garde la valeur existante pour ne pas l'écraser via le save. --}}
                @foreach($selectedEvents as $extra)
                    <input type="hidden" name="events[]" value="{{ $extra }}">
                @endforeach
            @endif

            <div class="row mt-3">
                <div class="col-md-4">
                    <x-adminlte-input-switch name="is_active" label="Endpoint actif"
                                             data-on-text="Oui" data-off-text="Non"
                                             :checked="old('is_active', $endpoint->is_active ?? false)"/>
                </div>
                <div class="col-md-4">
                    <x-adminlte-input name="retry_max" label="Retries max (sortant)" type="number"
                                      min="0" max="20"
                                      :value="old('retry_max', $endpoint->retry_max ?? 5)"/>
                </div>
                <div class="col-md-4">
                    <label>Backoff (secondes, séparés par virgule)</label>
                    <input type="text" name="retry_backoff_csv" class="form-control"
                           value="{{ old('retry_backoff_csv', is_array($endpoint->retry_backoff) ? implode(',', $endpoint->retry_backoff) : '60,300,900,1800,3600') }}"
                           disabled>
                    <small class="text-muted">Édition via seed / CLI pour l'instant.</small>
                </div>
            </div>
        </x-adminlte-card>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ __('general_content.save_trans_key') }}
            </button>
        </div>
    </form>

    @if(! $isNew)
        <x-adminlte-card title="Actions rapides" theme="secondary" theme-mode="outline" class="mt-4">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <form method="POST" action="{{ route('admin.integrations.endpoints.regenerate', $endpoint) }}"
                      class="d-inline mr-2"
                      onsubmit="return confirm('Régénérer les secrets ? Le partenaire devra être reconfiguré.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-sync-alt"></i> Régénérer les secrets
                    </button>
                </form>

                @if($endpoint->direction === 'outbound' && ! empty($endpoint->url))
                    <form method="POST" action="{{ route('admin.integrations.endpoints.test', $endpoint) }}"
                          class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-satellite-dish"></i> Tester la connexion
                        </button>
                    </form>
                    <small class="text-muted ml-2">POST signé sur <code>{{ rtrim($endpoint->url, '/') }}/api/plugin/ping</code></small>
                @endif
            </div>
            <small class="text-muted d-block mt-2">
                Après régénération, le secret HMAC apparaît en clair une seule fois en haut de la page — copie-le dans Nest2Prod avant de naviguer ailleurs.
            </small>
        </x-adminlte-card>
    @endif

    @if(! $isNew && $endpoint->system_code === 'n2p' && $endpoint->direction === 'outbound')
        <x-adminlte-card title="Catalogue tôles" theme="warning" theme-mode="outline" class="mt-4">
            <p class="text-muted small mb-2">
                Rejoue les réceptions de tôles matière première des N derniers jours vers Nest2Prod.
                Utile en setup client ou après un incident réseau. Idempotent côté N2P (upsert par <code>external_ref</code>).
            </p>
            <form method="POST" action="{{ route('admin.integrations.endpoints.resync-sheets', $endpoint) }}"
                  class="form-inline"
                  onsubmit="return confirm('Lancer la resynchronisation du catalogue tôles ?');">
                @csrf
                <label class="mr-2 mb-0" for="resync-days">Fenêtre (jours)</label>
                <input type="number" id="resync-days" name="days" value="30" min="1" max="365"
                       class="form-control form-control-sm mr-2" style="width: 100px;">
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="fas fa-sync"></i> Resynchroniser catalogue tôles
                </button>
            </form>
        </x-adminlte-card>
    @endif
@stop

@section('plugins.BootstrapSwitch', true)

@section('js')
<script>
    $(function () {
        $("input[data-bootstrap-switch]").each(function () {
            $(this).bootstrapSwitch('state', $(this).prop('checked'));
        });
    });
</script>
@stop
