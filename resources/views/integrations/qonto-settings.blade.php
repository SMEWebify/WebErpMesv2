@extends('adminlte::page')

@section('title', 'Qonto - Intégration')

@section('content_header')
    <h1>Qonto <small class="text-muted">Synchronisation clients</small></h1>
@stop

@section('content')

    @if(session('success'))
        <x-adminlte-alert theme="success" title="Succès" dismissable>
            {{ session('success') }}
        </x-adminlte-alert>
    @endif

    @if(! $featureEnabled)
        <x-adminlte-alert theme="warning" title="Intégration désactivée">
            Les variables <code>QONTO_CLIENT_ID</code> et <code>QONTO_CLIENT_SECRET</code> ne sont pas configurées.
        </x-adminlte-alert>
    @endif

    {{-- Connexion --}}
    <x-adminlte-card title="Connexion Qonto" theme="{{ $connection ? 'success' : 'secondary' }}" theme-mode="outline">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                @if($connection)
                    <span class="badge badge-success mr-2"><i class="fas fa-check-circle"></i> Connecté</span>
                    @if($connection->scope)
                        <small class="text-muted">Scopes : {{ $connection->scope }}</small>
                    @endif
                    @if($connection->last_sync_at)
                        <br><small class="text-muted mt-1 d-block">
                            Dernière sync : {{ $connection->last_sync_at->diffForHumans() }}
                            ({{ $connection->last_sync_at->format('d/m/Y H:i') }})
                        </small>
                    @endif
                @else
                    <span class="badge badge-secondary mr-2"><i class="fas fa-times-circle"></i> Non connecté</span>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if($connection)
                    <form method="POST" action="{{ route('admin.integrations.qonto.disconnect') }}"
                          onsubmit="return confirm('Déconnecter Qonto ?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-unlink mr-1"></i>Déconnecter
                        </button>
                    </form>
                @elseif($featureEnabled)
                    <a href="{{ route('admin.integrations.qonto.connect') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plug mr-1"></i>Connecter Qonto
                    </a>
                @endif
            </div>
        </div>
    </x-adminlte-card>

    @if($connection)

        {{-- Statistiques --}}
        <div class="row mb-3">
            <div class="col-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-link"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Liés</span>
                        <span class="info-box-number">{{ $stats['linked'] + $stats['created_in_qonto'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">À réviser</span>
                        <span class="info-box-number">{{ $stats['review_required'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-cloud-upload-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Créés dans Qonto</span>
                        <span class="info-box-number">{{ $stats['created_in_qonto'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-cloud-download-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Importés de Qonto</span>
                        <span class="info-box-number">{{ $stats['imported_from_qonto'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Synchronisation --}}
        <x-adminlte-card title="Synchronisation" theme="primary" theme-mode="outline">
            <p class="text-muted mb-3">
                Lance la réconciliation des clients WEM ↔ Qonto, puis pousse les clients non trouvés vers Qonto.
            </p>
            <form method="POST" action="{{ route('admin.integrations.qonto.sync') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-1"></i>Synchroniser maintenant
                </button>
            </form>
        </x-adminlte-card>

        {{-- Paramètres --}}
        <x-adminlte-card title="Paramètres" theme="secondary" theme-mode="outline">
            <form method="POST" action="{{ route('admin.integrations.qonto.settings') }}">
                @csrf
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="import_bidirectionnel" value="0">
                        <input type="checkbox" class="custom-control-input" id="import_bidirectionnel"
                               name="import_bidirectionnel" value="1"
                               {{ $connection->import_bidirectionnel ? 'checked' : '' }}>
                        <label class="custom-control-label" for="import_bidirectionnel">
                            Import bidirectionnel - créer dans WEM les clients Qonto non trouvés
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fas fa-save mr-1"></i>Enregistrer
                </button>
            </form>
        </x-adminlte-card>

        {{-- Revues en attente --}}
        @if($pendingReviews->isNotEmpty())
            <x-adminlte-card title="Revues en attente ({{ $pendingReviews->count() }})" theme="warning" theme-mode="outline">
                <p class="text-muted mb-3">
                    Ces correspondances sont ambiguës. Choisissez de les lier ou de les ignorer.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Client WEM</th>
                                <th>Candidat Qonto</th>
                                <th>Score</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingReviews as $review)
                                @php
                                    $wemName = $wemClientNames[$review->wem_client_id] ?? "Client #{{ $review->wem_client_id }}";
                                    $qontoName = $review->candidate_payload['best_candidate']['name'] ?? $review->qonto_client_id ?? '-';
                                @endphp
                                <tr>
                                    <td>{{ $wemName }}</td>
                                    <td>
                                        {{ $qontoName }}
                                        @if($review->qonto_client_id)
                                            <br><small class="text-muted">ID : {{ $review->qonto_client_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $review->matching_score >= 60 ? 'warning' : 'secondary' }}">
                                            {{ $review->matching_score }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <form method="POST"
                                              action="{{ route('admin.integrations.qonto.resolve', $review->id) }}"
                                              class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="link">
                                            @if($review->qonto_client_id)
                                                <input type="hidden" name="qonto_client_id" value="{{ $review->qonto_client_id }}">
                                            @endif
                                            <button type="submit" class="btn btn-xs btn-success">
                                                <i class="fas fa-check mr-1"></i>Lier
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('admin.integrations.qonto.resolve', $review->id) }}"
                                              class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="ignore">
                                            <button type="submit" class="btn btn-xs btn-outline-secondary">
                                                <i class="fas fa-ban mr-1"></i>Ignorer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-adminlte-card>
        @endif

    @endif

@stop

@section('css')
<style>
    .gap-2 { gap: 0.5rem; }
</style>
@stop
