@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.workshop_interface_trans_key') }}</h1>
@stop

@section('content')
    @php
    $taskLinesProps = [
        'endpoints'          => $props['endpoints'],
        'services'           => $props['services'],
        'statuses'           => $props['statuses'],
        'resources'          => $props['resources'],
        'default_status_ids' => $props['default_status_ids'],
    ];
    @endphp
    <div
        id="task-lines-app"
        data-endpoints="{{ json_encode($taskLinesProps['endpoints']) }}"
        data-services="{{ json_encode($taskLinesProps['services']) }}"
        data-statuses="{{ json_encode($taskLinesProps['statuses']) }}"
        data-resources="{{ json_encode($taskLinesProps['resources']) }}"
        data-default-status-ids="{{ json_encode($taskLinesProps['default_status_ids']) }}"
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
