@extends('adminlte::page')

@section('title', __('commercial.digital_twin.title'))

@section('content_header')
    <h1 class="mb-0"><i class="fas fa-cubes me-2"></i>{{ __('adminlte::menu.digital_twin_trans_key') }}</h1>
@stop

@section('content')
    <div class="commercial-feature">
        <div class="commercial-feature__hero">
            <span class="commercial-feature__badge"><i class="fas fa-star me-1"></i>{{ __('commercial.badge') }}</span>
            <div class="commercial-feature__icon"><i class="fas fa-cubes"></i></div>
            <h2 class="commercial-feature__title">{{ __('commercial.digital_twin.title') }}</h2>
            <p class="commercial-feature__lead">{{ __('commercial.digital_twin.lead') }}</p>
        </div>

        <div class="commercial-feature__body">
            <video class="commercial-feature__video mb-4" src="{{ asset('jumeau-02-flux.mp4') }}"
                   autoplay muted loop playsinline controls preload="metadata"
                   aria-label="{{ __('commercial.digital_twin.video') }}"></video>

            <div class="row">
                @foreach ([
                    ['icon' => 'fa-route',                'key' => 'flow'],
                    ['icon' => 'fa-flask',                'key' => 'simulation'],
                    ['icon' => 'fa-hourglass-half',       'key' => 'bottleneck'],
                ] as $point)
                    <div class="col-md-4 mb-3">
                        <div class="commercial-feature__point">
                            <i class="fas {{ $point['icon'] }}"></i>
                            <h5>{{ __("commercial.digital_twin.{$point['key']}_title") }}</h5>
                            <p>{{ __("commercial.digital_twin.{$point['key']}_text") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-2">
                <a href="https://nest2prod.com/" target="_blank" rel="noopener" class="btn btn-primary btn-lg commercial-feature__cta">
                    <i class="fas fa-envelope me-2"></i>{{ __('commercial.contact') }}
                </a>
                <div class="text-muted small mt-2">
                    <i class="fas fa-external-link-alt me-1"></i>nest2prod.com
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @include('include.commercial-feature-styles')
@stop
