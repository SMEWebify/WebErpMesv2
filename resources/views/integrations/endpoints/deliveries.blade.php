@extends('adminlte::page')

@section('title', 'Journal · ' . $endpoint->name)

@section('content_header')
    <h1>
        Journal des livraisons
        <small class="text-muted">{{ $endpoint->name }} · <code>{{ $endpoint->system_code }}</code></small>
    </h1>
    <a href="{{ route('admin.integrations.endpoints.index') }}" class="btn btn-link p-0">
        <i class="fas fa-arrow-left"></i> Retour à la liste
    </a>
@stop

@section('content')
    <x-adminlte-card title="Filtres" theme="secondary" theme-mode="outline" collapsible>
        <form method="GET" action="{{ route('admin.integrations.endpoints.deliveries', $endpoint) }}">
            <div class="row">
                <div class="col-md-3">
                    <label>Direction</label>
                    <select name="direction" class="form-control">
                        <option value="">Toutes</option>
                        <option value="in"  @selected(request('direction') === 'in')>Entrantes</option>
                        <option value="out" @selected(request('direction') === 'out')>Sortantes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Type d'event (préfixe)</label>
                    <input type="text" name="event_type" class="form-control" value="{{ request('event_type') }}" placeholder="task., stock., job.">
                </div>
                <div class="col-md-3">
                    <label>Statut</label>
                    <select name="status" class="form-control">
                        <option value="">Tous</option>
                        <option value="failed"  @selected(request('status') === 'failed')>En erreur</option>
                        <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.integrations.endpoints.deliveries', $endpoint) }}" class="btn btn-outline-secondary">
                        Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </x-adminlte-card>

    <x-adminlte-card title="Livraisons ({{ $deliveries->total() }})" theme="primary" theme-mode="outline">
        @if($deliveries->isEmpty())
            <p class="text-muted mb-0">Aucune livraison enregistrée pour ce filtre.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Reçue</th>
                            <th>Direction</th>
                            <th>Event type</th>
                            <th>Event ID</th>
                            <th>Statut HTTP</th>
                            <th>Durée</th>
                            <th>Traité à</th>
                            <th>Erreur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveries as $delivery)
                            <tr class="@if($delivery->error) table-danger @elseif($delivery->processed_at === null) table-warning @endif">
                                <td>{{ $delivery->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    @if($delivery->direction === 'in')
                                        <span class="badge badge-warning">IN</span>
                                    @else
                                        <span class="badge badge-info">OUT</span>
                                    @endif
                                </td>
                                <td><code>{{ $delivery->event_type }}</code></td>
                                <td><small class="text-muted">{{ Str::limit($delivery->event_id, 13, '…') }}</small></td>
                                <td>
                                    @if($delivery->http_status)
                                        <span class="badge {{ $delivery->http_status < 300 ? 'badge-success' : 'badge-danger' }}">
                                            {{ $delivery->http_status }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $delivery->duration_ms !== null ? $delivery->duration_ms.' ms' : '—' }}</td>
                                <td>{{ $delivery->processed_at?->format('H:i:s') ?? '—' }}</td>
                                <td>
                                    @if($delivery->error)
                                        <small class="text-danger">{{ Str::limit($delivery->error, 80) }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $deliveries->links() }}
            </div>
        @endif
    </x-adminlte-card>
@stop
