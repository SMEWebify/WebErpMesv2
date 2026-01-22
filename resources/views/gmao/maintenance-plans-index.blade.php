@extends('adminlte::page')

@section('title', __('general_content.gmao_maintenance_plans_page_title_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.gmao_maintenance_plans_page_title_trans_key') }}</h1>
@stop

@section('content')
    <x-adminlte-card title="{{ __('general_content.gmao_maintenance_plans_card_title_trans_key') }}" theme="primary" maximizable>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('general_content.asset_trans_key') }}</th>
                        <th>{{ __('general_content.title_trans_key') }}</th>
                        <th>{{ __('general_content.gmao_trigger_trans_key') }}</th>
                        <th>{{ __('general_content.gmao_fixed_date_trans_key') }}</th>
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
                                <a href="{{ route('gmao.maintenance-plans.edit', $plan->id) }}" class="btn btn-xs btn-default text-primary mx-1 shadow" title="{{ __('general_content.edit_trans_key') }}">
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
            <a href="{{ route('gmao.maintenance-plans.create') }}" class="btn btn-primary">{{ __('general_content.gmao_new_maintenance_plan_trans_key') }}</a>
            {{ $plans->links() }}
        </div>
    </x-adminlte-card>
@stop
