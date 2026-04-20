@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.stock_trans_key') }}</h1>
@stop

@section('content')
  <div
    id="stock-detail-app"
    data-initial="{{ json_encode($props['initial']) }}"
    data-endpoints="{{ json_encode($props['endpoints']) }}"
  ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.body.classList.add('sidebar-hidden');
    });
  </script>
@stop
