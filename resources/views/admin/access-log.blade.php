@extends('adminlte::page')

@section('title', __('audit.title'))

@section('content_header')
    <h1>{{ __('audit.title') }}</h1>
@stop

@section('content')
    @unless($enabled)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-1"></i>{{ __('audit.disabled') }}
        </div>
    @endunless

    <p class="text-secondary small mb-3">
        {{ __('audit.intro', ['minutes' => (int) config('audit.dedup_minutes', 10)]) }}
        {{ __('audit.retention', ['days' => (int) config('activitylog.delete_records_older_than_days', 365)]) }}
    </p>

    @php
        $accessLogEndpoints = [
            'list'   => route('admin.access-log.json.list'),
            'export' => route('admin.access-log.export'),
        ];
        $accessLogTrans = collect([
            'date', 'user', 'all_users', 'subject_type', 'all_types', 'subject_id', 'subject',
            'route', 'ip', 'from', 'to', 'reset', 'export_csv', 'loading', 'no_data',
        ])->mapWithKeys(fn ($key) => [$key => __('audit.' . $key)]);
    @endphp
    <div id="access-log-app"
         data-endpoints='@json($accessLogEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
         data-trans='@json($accessLogTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
         data-users='@json($users, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
         data-types='@json($types, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    ></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
