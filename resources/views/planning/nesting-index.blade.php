@extends('adminlte::page')

@section('title', 'Nesting')

@section('content_header')
    <h1><i class="fas fa-th me-2"></i>Nesting - Besoin matière</h1>
@stop

@section('content')
    {{-- Sans moteur d'imbrication (version open source), le bouton de calcul
         présente l'offre commerciale au lieu d'un résultat. --}}
    <div id="nesting-app"
         data-engine-enabled="{{ config('services.nestengine.enabled') ? '1' : '0' }}"
         data-commercial="{{ json_encode([
             'badge'   => __('commercial.badge'),
             'contact' => __('commercial.contact'),
         ] + __('commercial.nesting')) }}"
         @can('purchases-menu') data-reorder-url="{{ route('purchases.reorder') }}" @endcan></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @include('include.commercial-feature-styles')
@stop
