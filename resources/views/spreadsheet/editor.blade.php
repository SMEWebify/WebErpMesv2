@extends('adminlte::page')

@section('title', 'Éditeur de tableur')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $spreadsheet->name }}</h1>
        <span id="save-status" class="text-muted">Prêt</span>
    </div>
@stop

@section('content')
    <div id="univer-container" style="height: calc(100vh - 60px);"></div>

    <script>
        window.WEM_SPREADSHEET = {
            id: {{ $spreadsheet->id }},
            name: @json($spreadsheet->name),
            saveUrl: "{{ route('spreadsheet.save', $spreadsheet) }}",
            dataApiBase: "/api/spreadsheet/data",
            csrfToken: "{{ csrf_token() }}",
            sheets: @json($spreadsheet->sheets->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'data' => $s->data]))
        };
    </script>
    <script src="{{ mix('js/spreadsheet.js') }}"></script>
@stop
