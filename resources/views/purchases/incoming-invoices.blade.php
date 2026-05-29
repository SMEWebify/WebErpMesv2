@extends('adminlte::page')

@section('title', __('general_content.incoming_invoices_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.incoming_invoices_trans_key') }} — Factur-X</h1>
@stop

@section('content')
    <div id="incoming-invoices-index-app"
         data-endpoints='@json([
            "list"   => route("purchases.incoming.json.list"),
            "upload" => route("purchases.incoming.upload"),
         ])'
         data-locale="{{ str_replace('_', '-', app()->getLocale()) }}"
         data-currency="{{ optional(app('Factory'))->curency ?? 'EUR' }}">
    </div>
@stop
