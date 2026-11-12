@extends('adminlte::page')

@section('title', __('commercial.press_brake.title'))

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0"><i class="fas fa-drafting-compass me-2"></i>{{ __('commercial.press_brake.title') }}</h1>
        <a href="{{ route('methods.tool') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="fas fa-arrow-left me-1"></i>{{ __('adminlte::menu.methods_tools_trans_key') }}
        </a>
    </div>
@stop

@section('content')
    @if(isset($nest2bend) && ! $nest2bend['show_commercial'])
        <div class="card card-outline card-{{ $nest2bend['expired'] ? 'danger' : 'success' }} mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bezier-curve me-1"></i> {{ __('nest2bend.panel.title') }}</h3>
                <div class="card-tools">
                    <span class="badge text-bg-{{ $nest2bend['expired'] ? 'danger' : 'success' }}">{{ __('nest2bend.badge.' . $nest2bend['status']) }}</span>
                </div>
            </div>
            <div class="card-body">
                @if($nest2bend['expired'])
                    <div class="alert alert-danger">{{ __('nest2bend.panel.expired') }}</div>
                @elseif($nest2bend['warning'])
                    <div class="alert alert-warning">
                        {{ __('nest2bend.state.warning', ['etat' => __('nest2bend.etats.' . $nest2bend['etat']), 'echeance' => $nest2bend['echeance']?->format('d/m/Y') ?? '—']) }}
                    </div>
                @endif
                <p>{{ __('nest2bend.panel.lead') }}</p>
                <dl class="row mb-3">
                    @if($nest2bend['societe'])
                        <dt class="col-sm-3">{{ __('nest2bend.societe') }}</dt>
                        <dd class="col-sm-9">{{ $nest2bend['societe'] }}</dd>
                    @endif
                    @if($nest2bend['echeance'])
                        <dt class="col-sm-3">{{ __('nest2bend.echeance') }}</dt>
                        <dd class="col-sm-9">{{ $nest2bend['echeance']->format('d/m/Y') }}</dd>
                    @endif
                    @if($nest2bend['presses_max'])
                        <dt class="col-sm-3">{{ __('nest2bend.presses_max') }}</dt>
                        <dd class="col-sm-9">{{ $nest2bend['presses_max'] }}</dd>
                    @endif
                </dl>
                @if($nest2bend['app_url'])
                    <a href="{{ $nest2bend['app_url'] }}" target="_blank" rel="noopener" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-1"></i>{{ $nest2bend['expired'] ? __('nest2bend.button.renew') : __('nest2bend.open_app') }}
                    </a>
                @endif
            </div>
        </div>
    @else
    <div class="commercial-feature">
        <div class="commercial-feature__hero">
            <span class="commercial-feature__badge"><i class="fas fa-star me-1"></i>{{ __('commercial.badge') }}</span>
            <div class="commercial-feature__icon"><i class="fas fa-drafting-compass"></i></div>
            <h2 class="commercial-feature__title">{{ __('commercial.press_brake.title') }}</h2>
            <p class="commercial-feature__lead">{{ __('commercial.press_brake.lead') }}</p>
        </div>

        <div class="commercial-feature__body">
            {{-- Capture de NEST2BEND : affichée tant qu'aucune liaison n'est en place (écran commercial). --}}
            <figure class="commercial-feature__screenshot">
                <img src="{{ asset('images/commercial/nest2bend-simulation.png') }}"
                     alt="{{ __('commercial.press_brake.screenshot_alt') }}"
                     width="1541" height="840" loading="lazy" class="img-fluid">
                <figcaption>{{ __('commercial.press_brake.screenshot_caption') }}</figcaption>
            </figure>

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
                    <i class="fas fa-envelope me-2"></i>{{ __('commercial.contact') }}
                </a>
                <div class="text-muted small mt-2">
                    <i class="fas fa-external-link-alt me-1"></i>nest2prod.com
                </div>
            </div>
        </div>
    </div>
    @endif
@stop

@section('css')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @include('include.commercial-feature-styles')
@stop
