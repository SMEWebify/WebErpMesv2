@extends('adminlte::page')

@section('title', __('Production Trace'))

@section('content_header')
    <h1>{{ __('Production Trace') }} - {{ $serialNumber->serial_number }}</h1>
@stop

@section('right-sidebar')
@stop

@section('content')
<x-adminlte-card title="{{ __('Production Trace') }}" theme="info" maximizable>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Operation') }}</th>
                    <th>{{ __('Operator') }}</th>
                    <th>{{ __('Components') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timeline as $event)
                    <tr>
                        <td>{{ $event['date']->format('d/m/Y H:i') }}</td>
                        <td>{{ $event['operation'] }}</td>
                        <td>{{ $event['user'] }}</td>
                        <td>{{ $event['component'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-adminlte-card>
@stop

@section('css')
@stop

@section('js')
@stop
