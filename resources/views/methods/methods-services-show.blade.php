@extends('adminlte::page')

@section('title', __('general_content.service_trans_key') .' '. $service->label) 

@section('content_header')
    <h1>{{ __('general_content.service_trans_key') }} {{ $service->label }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="row">
    <!-- Infos du Service -->
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="warning" maximizable>
            <p><strong>Code :</strong> {{ $service->code }}</p>
            <p><strong>Label :</strong> {{ $service->label }}</p>
            <p><strong>Type :</strong> {{ $service->type }}</p>
            <p><strong>Taux Horaire :</strong> {{ number_format($service->hourly_rate, 2, ',', ' ') }} {{ $factory->curency }}</p>
            <p><strong>Marge :</strong> {{ number_format($service->margin, 2, ',', ' ') }} %</p>
            <p><strong>{{ __('general_content.supplier_trans_key') }} :</strong>
              @if($service->Suppliers->isNotEmpty())
                {{ $service->Suppliers->pluck('label')->implode(', ') }}
              @else
                -
              @endif
            </p>
            <p><strong>Créé le :</strong> {{ $service->getPrettyCreatedAttribute() }}</p>

            <div class="card-footer">
                <a href="{{ url()->previous() }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('general_content.back_trans_key') }}</a>
            </div>
          </x-adminlte-card>
    </div>

    <!-- Indicateurs financiers -->
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="orange" maximizable>
        @php
            $marginAmount = $service->hourly_rate * ($service->margin / 100);
            $billingRate  = $service->hourly_rate + $marginAmount;
        @endphp
        <div class="row text-center">
          <div class="col-6">
            <div class="info-box bg-blue">
              <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Taux horaire</span>
                <span class="info-box-number">{{ number_format($service->hourly_rate, 2, ',', ' ') }} {{ $factory->curency }}</span>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="info-box bg-success">
              <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Marge</span>
                <span class="info-box-number">{{ number_format($marginAmount, 2, ',', ' ') }} {{ $factory->curency }}</span>
              </div>
            </div>
          </div>
          <div class="col-6 mt-2">
            <div class="info-box bg-warning">
              <span class="info-box-icon"><i class="fas fa-tag"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Taux de facturation</span>
                <span class="info-box-number">{{ number_format($billingRate, 2, ',', ' ') }} {{ $factory->curency }}</span>
              </div>
            </div>
          </div>
          <div class="col-6 mt-2">
            <div class="info-box bg-secondary">
              <span class="info-box-icon"><i class="fas fa-chart-bar"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Taux de marge</span>
                <span class="info-box-number">{{ number_format($service->margin, 2, ',', ' ') }} %</span>
              </div>
            </div>
          </div>
        </div>
      </x-adminlte-card>
    </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
