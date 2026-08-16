@extends('adminlte::page')

@section('title', 'Intégrations')

@section('content_header')
    <h1>Intégrations <small class="text-muted">connecteurs et flux externes</small></h1>
@stop

@section('content')

    @if(session('success'))
        <x-adminlte-alert theme="success" title="{{ __('general_content.success_trans_key') }}" dismissable>
            {{ session('success') }}
        </x-adminlte-alert>
    @endif

    <div class="row">

        {{-- ─────────────────────────── Qonto ─────────────────────────── --}}
        <div class="col-md-6">
            <div class="card card-outline {{ $qonto['connected'] && ! $qonto['expired'] ? 'card-success' : ($qonto['configured'] ? 'card-secondary' : 'card-warning') }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-university mr-1"></i> Qonto
                    </h3>
                    <div class="card-tools">
                        @if(! $qonto['configured'])
                            <span class="badge badge-warning">Non configuré</span>
                        @elseif(! $qonto['connected'])
                            <span class="badge badge-secondary">Déconnecté</span>
                        @elseif($qonto['expired'])
                            <span class="badge badge-danger">Jeton expiré</span>
                        @else
                            <span class="badge badge-success">Connecté</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Banque - synchronisation des clients (OAuth2, par utilisateur).
                    </p>

                    @if(! $qonto['configured'])
                        <p class="mb-0">
                            <code>QONTO_CLIENT_ID</code> / <code>QONTO_CLIENT_SECRET</code> absents du <code>.env</code>.
                        </p>
                    @else
                        <dl class="row mb-0">
                            @if($qonto['organization'])
                                <dt class="col-6">Organisation</dt>
                                <dd class="col-6"><code>{{ $qonto['organization'] }}</code></dd>
                            @endif

                            <dt class="col-6">Clients mappés</dt>
                            <dd class="col-6">{{ $qonto['mapped_clients'] }}</dd>

                            <dt class="col-6">À arbitrer</dt>
                            <dd class="col-6">
                                @if($qonto['pending_reviews'] > 0)
                                    <span class="badge badge-warning">{{ $qonto['pending_reviews'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>

                            <dt class="col-6">Import bidirectionnel</dt>
                            <dd class="col-6">{{ $qonto['bidirectionnel'] ? 'Oui' : 'Non' }}</dd>

                            <dt class="col-6">Dernière synchro</dt>
                            <dd class="col-6">
                                @if($qonto['last_sync_at'])
                                    <span title="{{ $qonto['last_sync_at'] }}">{{ $qonto['last_sync_at']->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted">jamais</span>
                                @endif
                            </dd>
                        </dl>
                    @endif
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.integrations.qonto.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-cog"></i> Configurer
                    </a>
                </div>
            </div>
        </div>

        {{-- Carte dédiée Nest2Prod SUPPRIMÉE (refactor 2026-08-12) :
             N2P se gère désormais comme n'importe quel endpoint webhook,
             visible dans la carte générique "Endpoints webhook" ci-dessous.
             Contrairement à Qonto (OAuth utilisateur) ou PDP (env config),
             il n'a plus rien de spécifique qui justifie une carte dédiée. --}}

        {{-- ─────────────────── Endpoints webhook génériques ─────────────────── --}}
        <div class="col-md-6">
            <div class="card card-outline {{ $endpoints['failing'] > 0 ? 'card-danger' : 'card-info' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exchange-alt mr-1"></i> Endpoints webhook
                    </h3>
                    <div class="card-tools">
                        @if($endpoints['failing'] > 0)
                            <span class="badge badge-danger">{{ $endpoints['failing'] }} en erreur</span>
                        @else
                            <span class="badge badge-info">{{ $endpoints['active'] }} actif(s)</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Flux partenaires signés (bearer / HMAC), entrants et sortants.
                    </p>
                    <dl class="row mb-0">
                        <dt class="col-6">Configurés</dt>
                        <dd class="col-6">{{ $endpoints['total'] }}</dd>

                        <dt class="col-6">Sens</dt>
                        <dd class="col-6">{{ $endpoints['inbound'] }} entrant(s) / {{ $endpoints['outbound'] }} sortant(s)</dd>

                        <dt class="col-6">Systèmes</dt>
                        <dd class="col-6">
                            @forelse($endpoints['systems'] as $system)
                                <code>{{ $system }}</code>@if(! $loop->last), @endif
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </dd>
                    </dl>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.integrations.endpoints.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> Gérer les endpoints
                    </a>
                </div>
            </div>
        </div>

        {{-- ─────────────────────────── n8n ─────────────────────────── --}}
        <div class="col-md-6">
            <div class="card card-outline {{ $n8n['failing'] > 0 ? 'card-danger' : ($n8n['active'] > 0 ? 'card-success' : 'card-secondary') }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-project-diagram mr-1"></i> n8n
                    </h3>
                    <div class="card-tools">
                        @if($n8n['failing'] > 0)
                            <span class="badge badge-danger">{{ $n8n['failing'] }} en erreur</span>
                        @elseif($n8n['active'] > 0)
                            <span class="badge badge-success">{{ $n8n['active'] }} actif(s)</span>
                        @elseif($n8n['total'] > 0)
                            <span class="badge badge-secondary">Inactif</span>
                        @else
                            <span class="badge badge-light">Non branché</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Automation open source — un webhook n8n reçoit les événements
                        ERP (devis, commandes, tâches...) ou déclenche des actions dans
                        l'ERP (créer une commande, attacher un fichier...).
                    </p>
                    @if($n8n['total'] === 0)
                        <p class="mb-0">
                            <small class="text-muted">
                                Sens sortant : l'ERP appelle l'URL webhook n8n signée en HMAC.
                                Sens entrant : n8n POST vers <code>/api/integrations/n8n/inbound</code>.
                            </small>
                        </p>
                    @else
                        <dl class="row mb-0">
                            <dt class="col-6">Endpoints</dt>
                            <dd class="col-6">{{ $n8n['total'] }}</dd>

                            <dt class="col-6">Sens</dt>
                            <dd class="col-6">{{ $n8n['inbound'] }} entrant(s) / {{ $n8n['outbound'] }} sortant(s)</dd>

                            <dt class="col-6">Dernier succès</dt>
                            <dd class="col-6">
                                @if($n8n['last_success_at'])
                                    <span title="{{ $n8n['last_success_at'] }}">{{ $n8n['last_success_at']->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted">jamais</span>
                                @endif
                            </dd>
                        </dl>
                    @endif
                </div>
                <div class="card-footer text-right">
                    @if($n8n['total'] > 0)
                        <a href="{{ route('admin.integrations.endpoints.index') }}" class="btn btn-sm btn-default">
                            <i class="fas fa-list"></i> Voir les endpoints
                        </a>
                    @endif
                    <a href="{{ route('admin.integrations.endpoints.create', ['preset' => 'n8n']) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un endpoint n8n
                    </a>
                </div>
            </div>
        </div>

        {{-- ──────────────────── PDP (facturation électronique) ──────────────────── --}}
        <div class="col-md-6">
            <div class="card card-outline {{ $pdp['enabled'] ? 'card-success' : 'card-secondary' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice mr-1"></i> Facturation électronique (PDP)
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $pdp['enabled'] ? 'success' : 'secondary' }}">
                            {{ $pdp['enabled'] ? 'Opérationnelle' : 'Indisponible' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Dépôt des factures auprès de la plateforme de dématérialisation.
                    </p>
                    <dl class="row mb-0">
                        <dt class="col-6">Driver actif</dt>
                        <dd class="col-6"><code>{{ $pdp['driver'] }}</code></dd>

                        <dt class="col-6">Drivers disponibles</dt>
                        <dd class="col-6">
                            @foreach($pdp['available'] as $key)
                                <code>{{ $key }}</code>@if(! $loop->last), @endif
                            @endforeach
                        </dd>

                        <dt class="col-6">Factures déposées</dt>
                        <dd class="col-6">{{ $pdp['total'] }}</dd>

                        @if($pdp['total'] > 0)
                            <dt class="col-6">Par statut</dt>
                            <dd class="col-6">
                                @foreach($pdp['counts'] as $status => $count)
                                    <span class="badge badge-{{ in_array($status, ['rejected', 'refused'], true) ? 'danger' : 'light' }} mr-1">
                                        {{ $status }} : {{ $count }}
                                    </span>
                                @endforeach
                            </dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer text-muted">
                    <small>
                        Configurée par <code>PDP_DRIVER</code> dans le <code>.env</code> - le driver
                        <code>qonto</code> réutilise la connexion Qonto ci-dessus.
                    </small>
                </div>
            </div>
        </div>

    </div>
@stop
