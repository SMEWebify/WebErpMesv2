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
    <style>
        .commercial-feature {
            max-width: 920px;
            margin: 1.5rem auto;
            background: #fff;
            border-radius: .75rem;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .08);
            overflow: hidden;
        }
        .commercial-feature__hero {
            position: relative;
            padding: 3rem 1.5rem 2.5rem;
            text-align: center;
            color: #fff;
            background: linear-gradient(135deg, #1f3c88 0%, #2f80ed 60%, #56ccf2 100%);
        }
        .commercial-feature__badge {
            display: inline-block;
            padding: .3rem .85rem;
            border-radius: 2rem;
            background: rgba(255, 255, 255, .18);
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .commercial-feature__icon {
            width: 88px;
            height: 88px;
            margin: 1.25rem auto 1rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .commercial-feature__title {
            font-weight: 700;
            margin-bottom: .5rem;
        }
        .commercial-feature__lead {
            max-width: 560px;
            margin: 0 auto;
            font-size: 1.1rem;
            opacity: .92;
        }
        .commercial-feature__body {
            padding: 2rem 1.5rem 2.25rem;
        }
        .commercial-feature__point {
            height: 100%;
            padding: 1.25rem;
            text-align: center;
            border: 1px solid #e9ecef;
            border-radius: .5rem;
        }
        .commercial-feature__point i {
            font-size: 1.6rem;
            color: #2f80ed;
            margin-bottom: .6rem;
        }
        .commercial-feature__point h5 {
            font-weight: 600;
        }
        .commercial-feature__point p {
            margin-bottom: 0;
            color: #6c757d;
        }
        .commercial-feature__cta {
            padding-left: 2rem;
            padding-right: 2rem;
            border-radius: 2rem;
        }
        .dark-mode .commercial-feature { background: #343a40; }
        .dark-mode .commercial-feature__point { border-color: #4b545c; }
        .dark-mode .commercial-feature__point p { color: #adb5bd; }
    </style>
@stop
