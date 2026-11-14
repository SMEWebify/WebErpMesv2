@extends('adminlte::page')

@section('title', __('scan_station.screen.title'))

@section('content')
  {{-- Écran de scan atelier (docs/pointage-scan.md) : React ScanStation. --}}
  <div id="scan-station-app"
       data-scan-url='{{ route("workshop.scan.store") }}'
       data-mode='{{ $mode }}'
       data-mode-label='{{ $modeLabel }}'
       data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
  </div>
@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Écran atelier en plein écran : la sidebar est masquée (public/css/wem-shell.css).
            document.body.classList.add('sidebar-hidden');
        });
    </script>
@stop
