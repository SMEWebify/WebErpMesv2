@extends('adminlte::page')

@section('title', __('general_content.gantt_trans_key'))

@section('content_header')
	<div class="row mb-2">
		<div class="col-sm-6">
			<h1>{{ __('general_content.gantt_trans_key') }}</h1>
		</div>
		<div class="col-sm-6">
			<button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationDate">
				{{ __('general_content.gantt_info_1_trans_key') }} ({{ $countTaskNullDate }})
			</button>
		</div>
	</div>
@stop

@section('right-sidebar')

@section('content')
	<x-adminlte-card theme="lime" theme-mode="outline">
		@include('include.alert-result')
		<form action="{{ route('production.gantt') }}" method="GET">
			<div class="row">
				<div class="form-group col-4">
					<x-adminlte-select2 name="order_id" id="order_id"
						label="{{ __('general_content.order_trans_key') }}"
						label-class="text-secondary"
						igroup-size="s"
						data-placeholder="{{ __('general_content.order_trans_key') }}">
						<x-slot name="prependSlot">
							<div class="input-group-text bg-gradient-secondary">
								<i class="fas fa-list"></i>
							</div>
						</x-slot>
						<option value="">— {{ __('general_content.order_trans_key') }} —</option>
						@foreach ($orderList as $order)
							<option value="{{ $order->id }}" @selected($order->id == $orderId)>
								{{ $order->code }}{{ $order->label ? ' — ' . $order->label : '' }}
							</option>
						@endforeach
					</x-adminlte-select2>
				</div>
				<div class="form-group col-2">
					<x-adminlte-button class="btn-flat" type="submit"
						label="{{ __('general_content.submit_trans_key') }}"
						theme="danger" icon="fas fa-lg fa-save"/>
				</div>
			</div>
		</form>
	</x-adminlte-card>

	<x-adminlte-card theme="lime" theme-mode="outline">
		<div
			id="gantt-chart-app"
			data-order-id="{{ $orderId ?? '' }}"
			data-api-base="/production/gantt/order"
			data-trans='{!! json_encode([
				"view_trans_key"   => __("general_content.view_trans_key"),
				"select_order_trans_key" => __("general_content.order_trans_key"),
			]) !!}'
		></div>
	</x-adminlte-card>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@vite('node_modules/frappe-gantt/dist/frappe-gantt.css')
<style>
    .gantt-order-line .bar {
        fill: #28a745 !important;
    }
    .gantt-order-root .bar {
        fill: #007bff !important;
    }
    .gantt-popup {
        font-size: 0.85rem;
        min-width: 180px;
    }
</style>
@stop
