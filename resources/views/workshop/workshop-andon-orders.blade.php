@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.workshop_interface_trans_key') }}</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Commandes en retard -->
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> {{ __('general_content.late_orders_trans_key') }}</h3>
                </div>
                <div class="card-body">
                    @forelse($lateOrders as $order)
                        <div class="alert alert-light text-dark">
                            <strong>{{ $order->order->code }}</strong><br>
                            <small><i class="fas fa-clock"></i> {{ $order->order->delay_info }}</small>
                        </div>
                    @empty
                        <p>{{ __('general_content.no_late_orders_trans_key') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Commandes à livrer aujourd'hui -->
        <div class="col-md-8">
            <div class="card bg-primary text-white">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-truck"></i> {{ __('general_content.orders_to_deliver_today_trans_key') }}</h3>
                </div>
                <div class="card-body row">
                    @forelse($incomingOrders as $order)
                        <div class="col-md-6">
                            <div class="alert alert-light text-dark">
                                <strong>{{ $order->order->code }}</strong><br>
                                <small><i class="fas fa-calendar-day"></i>{{ __('general_content.delivery_scheduled_today_trans_key') }}</small>
                            </div>
                        </div>
                    @empty
                        <p>{{ __('general_content.no_orders_scheduled_today_trans_key') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Commandes prêtes -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-success text-white">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check-circle"></i>{{ __('general_content.ready_orders_trans_key') }}</h3>
                </div>
                <div class="card-body row">
                    @forelse($readyOrders as $order)
                        <div class="col-md-4">
                            <div class="alert alert-light text-dark">
                                <strong>{{ $order->order->code }}</strong><br>
                                <small><i class="fas fa-clock"></i> {{ $order->order->delay_info }}</small>
                            </div>
                        </div>
                    @empty
                        <p>{{ __('general_content.no_ready_orders_trans_key') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    <style>
        .sidebar-hidden {
            display: none !important;
        }
    </style>
@stop

@section('js')
@stop
