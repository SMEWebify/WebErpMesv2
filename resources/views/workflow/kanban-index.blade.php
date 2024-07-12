@extends('adminlte::page')

@section('title', __('general_content.workflow_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.workflow_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
    <x-InfocalloutComponent note="{{ __('general_content.workflow_info_1_trans_key') }}"  />

    <!-- Formulaire de sélection du service -->
    <form method="GET" action="{{ route('production.kanban') }}" class="mb-4">
        <div class="form-group">
            <label for="methods_services_id">Select Service:</label>
            <select name="methods_services_id" id="methods_services_id" class="form-control">
                <option value="">All Services</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" {{ request()->input('methods_services_id') == $service->id ? 'selected' : '' }}>
                        {{ $service->label }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <div id="card">
        <kanban-board :initial-data="{{ $tasks }}"></kanban-board>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/app.css">
@stop

@section('js')
    <script src="/js/app.js"></script>
@stop