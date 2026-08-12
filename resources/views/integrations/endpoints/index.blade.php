@extends('adminlte::page')

@section('title', 'Intégrations')

@section('content_header')
    <h1>Intégrations — endpoints</h1>
@stop

@section('content')
    @if(session('success'))
        <x-adminlte-alert theme="success" title="{{ __('general_content.success_trans_key') }}">
            {{ session('success') }}
        </x-adminlte-alert>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.integrations.endpoints.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel endpoint
        </a>
    </div>

    <x-adminlte-card title="Endpoints configurés" theme="primary" theme-mode="outline">
        @if($endpoints->isEmpty())
            <p class="text-muted mb-0">Aucun endpoint pour l'instant.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Système</th>
                            <th>Nom</th>
                            <th>Direction</th>
                            <th>Auth</th>
                            <th>Actif</th>
                            <th>Dernière activité</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($endpoints as $endpoint)
                            <tr>
                                <td><code>{{ $endpoint->system_code }}</code></td>
                                <td>{{ $endpoint->name }}</td>
                                <td>
                                    @if($endpoint->direction === 'outbound')
                                        <span class="badge badge-info">Sortant</span>
                                    @else
                                        <span class="badge badge-warning">Entrant</span>
                                    @endif
                                </td>
                                <td><code>{{ $endpoint->auth_method }}</code></td>
                                <td>
                                    @if($endpoint->is_active)
                                        <span class="badge badge-success">Oui</span>
                                    @else
                                        <span class="badge badge-secondary">Non</span>
                                    @endif
                                </td>
                                <td>
                                    @if($endpoint->last_error_at && (! $endpoint->last_success_at || $endpoint->last_error_at->gt($endpoint->last_success_at)))
                                        <span class="text-danger" title="{{ $endpoint->last_error_message }}">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $endpoint->last_error_at->diffForHumans() }}
                                        </span>
                                    @elseif($endpoint->last_success_at)
                                        <span class="text-success">
                                            <i class="fas fa-check"></i> {{ $endpoint->last_success_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.integrations.endpoints.deliveries', $endpoint) }}" class="btn btn-sm btn-outline-secondary" title="Journal">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <a href="{{ route('admin.integrations.endpoints.edit', $endpoint) }}" class="btn btn-sm btn-outline-primary" title="Éditer">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.integrations.endpoints.destroy', $endpoint) }}" class="d-inline" onsubmit="return confirm('Supprimer cet endpoint ? Toutes ses livraisons seront également effacées.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-adminlte-card>
@stop
