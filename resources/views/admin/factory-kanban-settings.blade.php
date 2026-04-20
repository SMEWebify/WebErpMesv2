@extends('adminlte::page')

@section('title', __('general_content.workflow_settings_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.workflow_settings_trans_key') }}</h1>
@stop

@section('content')
    <x-InfocalloutComponent note="{{ __('general_content.kanban_setting_note_trans_key') }}" />

    @php
    $trans = [
        'title'          => __('general_content.title_trans_key'),
        'order'          => __('general_content.order_trans_key'),
        'select_type'    => __('general_content.select_type_trans_key'),
        'add'            => __('general_content.add_trans_key'),
        'description'    => __('general_content.description_trans_key'),
        'action'         => __('general_content.action_trans_key'),
        'no_data'        => __('general_content.no_data_trans_key'),
        'sort'           => __('general_content.sort_trans_key'),
        'added'          => __('general_content.success_trans_key'),
        'updated'        => __('general_content.success_trans_key'),
        'deleted'        => __('general_content.success_trans_key'),
        'error'          => __('general_content.error_trans_key'),
        'title_required' => __('general_content.title_trans_key'),
        'title_unique'   => __('general_content.error_trans_key'),
    ];
    @endphp

    <div
        id="kanban-setting-app"
        data-endpoints="{{ json_encode($props['endpoints']) }}"
        data-trans="{{ json_encode($trans) }}"
    ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop


@section('js')
@stop
