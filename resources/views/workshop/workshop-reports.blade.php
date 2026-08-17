@extends('adminlte::page')

@section('title', 'Rapports atelier')

@section('content_header')
  <h1>
    Rapports atelier
    <small class="text-muted">{{ $report['period']['label'] }}</small>
  </h1>
@stop

@section('content')
  <div id="workshop-reports-app"
       data-initial='@json($report, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-endpoints='@json($endpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
  </div>

  <div class="text-center mb-4">
    <a href="{{ route('workshop') }}" class="btn btn-default btn-lg">
      <i class="fas fa-arrow-left mr-2"></i>{{ __('general_content.back_trans_key') }}
    </a>
  </div>
@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $("body").addClass("sidebar-hidden");
        });
    </script>
@stop
