@extends('adminlte::page')

@section('title', 'Nouveau tableur')

@section('content_header')
    <h1>Nouveau tableur</h1>
@stop

@section('content')
    <x-adminlte-card theme="light" theme-mode="outline">
        <form method="POST" action="{{ route('spreadsheet.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description') }}</textarea>
                @error('description')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="{{ route('spreadsheet.index') }}" class="btn btn-default">Annuler</a>
        </form>
    </x-adminlte-card>
@stop
