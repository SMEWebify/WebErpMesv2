@extends('adminlte::page')

@section('title', __('general_content.documents_trans_key'))

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="mb-0">{{ __('general_content.documents_trans_key') }}</h1>
    </div>
@stop

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id="document-table-app"
                 data-documents='@json($documents, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
                 data-translations='@json($translations, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/app.css">
@stop

@section('js')
    <script src="/js/app.js"></script>
@stop
