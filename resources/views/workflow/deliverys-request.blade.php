@extends('adminlte::page')

@section('title', __('general_content.deliverys_notes_request_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.deliverys_notes_request_trans_key') }}</h1>
@stop

@section('content')
<div
    id="deliverys-request-app"
    data-code="{{ $reactProps['code'] }}"
    data-user-id="{{ $reactProps['userId'] }}"
    data-can-manage-stock="{{ $reactProps['canManageStock'] ? 'true' : 'false' }}"
    data-users='@json($reactProps['users'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-companies='@json($reactProps['companies'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop