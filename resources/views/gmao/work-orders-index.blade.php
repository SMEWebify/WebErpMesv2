@extends('adminlte::page')

@section('title', 'GMAO - Work Orders')

@section('content_header')
    <h1>GMAO - Work Orders</h1>
@stop

@section('content')
    <x-adminlte-card title="Work Orders" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Asset</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Requested at</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workOrders as $workOrder)
                        <tr>
                            <td>{{ $workOrder->id }}</td>
                            <td>{{ $workOrder->asset?->name }}</td>
                            <td><a href="{{ route('gmao.work-orders.show', $workOrder->id) }}">{{ $workOrder->title }}</a></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $workOrder->priority)) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}</td>
                            <td>{{ optional($workOrder->requested_at)->format('Y-m-d') }}</td>
                            <td class="text-right">
                                <a href="{{ route('gmao.work-orders.edit', $workOrder->id) }}" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Edit">
                                    <i class="fa fa-lg fa-fw fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">{{ __('general_content.no_data_trans_key') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('gmao.work-orders.create') }}" class="btn btn-primary">New Work Order</a>
            {{ $workOrders->links() }}
        </div>
    </x-adminlte-card>
@stop
