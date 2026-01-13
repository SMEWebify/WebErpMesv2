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

    <!-- Graphique -->
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="orange" maximizable>
          <canvas id="serviceChart"></canvas>
      </x-adminlte-card>
    </div>
</div>
@stop

@section('css')
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('serviceChart').getContext('2d');
            var serviceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Taux Horaire (€)', 'Marge (€)'],
                    datasets: [{
                        label: 'Valeurs (€)',
                        data: [
                            {{ $service->hourly_rate }}, 
                            {{ ($service->hourly_rate * (1 + $service->margin / 100))-$service->hourly_rate }}
                        ],
                        backgroundColor: ['#007bff', '#28a745'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
@stop
