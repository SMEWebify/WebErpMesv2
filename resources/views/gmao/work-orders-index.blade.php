@extends('adminlte::page')

@section('title', __('general_content.gmao_work_orders_page_title_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.gmao_work_orders_page_title_trans_key') }}</h1>
@stop

@section('content')
    <x-adminlte-card title="{{ __('general_content.gmao_work_orders_card_title_trans_key') }}" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('general_content.asset_trans_key') }}</th>
                        <th>{{ __('general_content.title_trans_key') }}</th>
                        <th>{{ __('general_content.type_trans_key') }}</th>
                        <th>{{ __('general_content.priority_trans_key') }}</th>
                        <th>{{ __('general_content.status_trans_key') }}</th>
                        <th>{{ __('general_content.gmao_technician_trans_key') }}</th>
                        <th>{{ __('general_content.gmao_requested_at_trans_key') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workOrders as $workOrder)
                        <tr>
                            <td>{{ $workOrder->id }}</td>
                            <td>{{ $workOrder->asset?->name }}</td>
                            <td><a href="{{ route('gmao.work-orders.show', $workOrder->id) }}">{{ $workOrder->title }}</a></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $workOrder->work_type)) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $workOrder->priority)) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}</td>
                            <td>{{ $workOrder->technician?->name ?? __('general_content.not_available_trans_key') }}</td>
                            <td>{{ optional($workOrder->requested_at)->format('Y-m-d') }}</td>
                            <td class="text-right">
                                <a href="{{ route('gmao.work-orders.edit', $workOrder->id) }}" class="btn btn-xs btn-default text-primary mx-1 shadow" title="{{ __('general_content.edit_trans_key') }}">
                                    <i class="fa fa-lg fa-fw fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">{{ __('general_content.no_data_trans_key') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('gmao.work-orders.create') }}" class="btn btn-primary">{{ __('general_content.gmao_new_work_order_trans_key') }}</a>
            {{ $workOrders->links() }}
        </div>
    </x-adminlte-card>
@stop
