@extends('adminlte::page')

@section('title', __('commercial.press_brake.title'))

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0"><i class="fas fa-drafting-compass mr-2"></i>{{ __('commercial.press_brake.title') }}</h1>
        <a href="{{ route('methods.tool') }}" class="btn btn-sm btn-outline-secondary ml-auto">
            <i class="fas fa-arrow-left mr-1"></i>{{ __('adminlte::menu.methods_tools_trans_key') }}
        </a>
    </div>
@stop

@section('content')
    <div class="commercial-feature">
        <div class="commercial-feature__hero">
            <span class="commercial-feature__badge"><i class="fas fa-star mr-1"></i>{{ __('commercial.badge') }}</span>
            <div class="commercial-feature__icon"><i class="fas fa-drafting-compass"></i></div>
            <h2 class="commercial-feature__title">{{ __('commercial.press_brake.title') }}</h2>
            <p class="commercial-feature__lead">{{ __('commercial.press_brake.lead') }}</p>
        </div>

        <div class="commercial-feature__body">
            <div class="row">
                @foreach ([
                    ['icon' => 'fa-ruler-combined', 'key' => 'designer'],
                    ['icon' => 'fa-layer-group',    'key' => 'simulation'],
                    ['icon' => 'fa-book-open',      'key' => 'catalog'],
                ] as $point)
                    <div class="col-md-4 mb-3">
                        <div class="commercial-feature__point">
                            <i class="fas {{ $point['icon'] }}"></i>
                            <h5>{{ __("commercial.press_brake.{$point['key']}_title") }}</h5>
                            <p>{{ __("commercial.press_brake.{$point['key']}_text") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-2">
                <a href="https://nest2prod.com/" target="_blank" rel="noopener" class="btn btn-primary btn-lg commercial-feature__cta">
                    <i class="fas fa-envelope mr-2"></i>{{ __('commercial.contact') }}
                </a>
                <div class="text-muted small mt-2">
                    <i class="fas fa-external-link-alt mr-1"></i>nest2prod.com
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @include('include.commercial-feature-styles')
@stop
