@extends('adminlte::page')

@section('title', 'Tableurs')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Tableurs</h1>
        <a href="{{ route('spreadsheet.create') }}" class="btn btn-primary">Nouveau tableur</a>
    </div>
@stop

@section('content')
    <x-adminlte-card theme="light" theme-mode="outline">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Créé par</th>
                        <th>Mis à jour</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spreadsheets as $spreadsheet)
                        <tr>
                            <td>{{ $spreadsheet->name }}</td>
                            <td>{{ $spreadsheet->description }}</td>
                            <td>{{ $spreadsheet->creator->name ?? '-' }}</td>
                            <td>{{ optional($spreadsheet->updated_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('spreadsheet.edit', $spreadsheet) }}" class="btn btn-sm btn-info">Éditer</a>
                                <form action="{{ route('spreadsheet.destroy', $spreadsheet) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Supprimer ce tableur ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Aucun tableur disponible.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $spreadsheets->links() }}
        </div>
    </x-adminlte-card>
@stop
