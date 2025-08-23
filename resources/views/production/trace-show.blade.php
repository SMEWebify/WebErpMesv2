@extends('adminlte::page')

@section('title', 'Production Trace')

@section('content_header')
    <h1>Production Trace</h1>
@stop

@section('content')
    <h3>Serial: {{ $serialNumber->serial_number }}</h3>
    <ul>
        @foreach($serialNumber->components as $component)
            <li>
                <a href="{{ route('production.trace.show', $component->componentSerial) }}">
                    Serial: {{ $component->componentSerial->serial_number }}
                </a>
            </li>
        @endforeach
    </ul>
@stop
