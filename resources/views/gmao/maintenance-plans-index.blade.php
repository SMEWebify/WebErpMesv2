@extends('adminlte::page')

@section('title', 'GMAO - Maintenance Plans')

@section('content_header')
    <h1>GMAO - Maintenance Plans</h1>
@stop

@section('content')
    <x-adminlte-card title="Maintenance Plans" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Asset</th>
                        <th>Title</th>
                        <th>Trigger</th>
                        <th>Fixed date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>{{ $plan->id }}</td>
                            <td>{{ $plan->asset?->name }}</td>
                            <td><a href="{{ route('gmao.maintenance-plans.show', $plan->id) }}">{{ $plan->title }}</a></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }} {{ $plan->trigger_value ? '(' . $plan->trigger_value . ')' : '' }}</td>
                            <td>{{ optional($plan->fixed_date)->format('Y-m-d') }}</td>
                            <td class="text-right">
                                <a href="{{ route('gmao.maintenance-plans.edit', $plan->id) }}" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Edit">
                                    <i class="fa fa-lg fa-fw fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('general_content.no_data_trans_key') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('gmao.maintenance-plans.create') }}" class="btn btn-primary">New Maintenance Plan</a>
            {{ $plans->links() }}
        </div>
    </x-adminlte-card>
@stop
