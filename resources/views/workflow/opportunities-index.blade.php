@extends('adminlte::page')

@section('title', __('general_content.opportunities_trans_key'))

@section('content_header')
<div class="row mb-2">
    <h1>{{ __('general_content.opportunities_trans_key')}}</h1>
</div>
@stop

@section('right-sidebar')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.statistiques_trans_key') }}</h3>
        </div>
        <div class="card-body">
          <canvas id="donutChart" width="400" height="400"></canvas>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.statistiques_trans_key') }}</h3>
        </div>
        <div class="card-body">
          <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-9">
        @livewire('opportunities-index')
    </div>
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop