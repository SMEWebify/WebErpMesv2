@extends('adminlte::page')

@section('title', $asset->name)

@section('content_header')
    <h1>{{ $asset->name }}</h1>
@stop

@section('content')
    <x-adminlte-card title="{{ $asset->name }}" theme="primary" maximizable>
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="d-flex align-items-start mb-4">
                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <div class="text-muted text-sm">Category</div>
                        <div class="h6 mb-0">{{ $asset->category }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-4">
                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <div class="text-muted text-sm">{{ __('general_content.ressource_trans_key') }}</div>
                        <div class="h6 mb-0">{{ $asset->methodsRessource?->label ?? __('general_content.no_data_trans_key') }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-4">
                    <div class="bg-light text-success rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <div class="text-muted text-sm">Acquisition value</div>
                        <div class="h6 mb-0">{{ $asset->acquisition_value }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-start mb-4">
                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <div class="text-muted text-sm">Acquisition date</div>
                        <div class="h6 mb-0">{{ $asset->acquisition_date->format('Y-m-d') }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-4">
                    <div class="bg-light text-warning rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 42px; height: 42px;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div class="text-muted text-sm">Depreciation duration</div>
                        <div class="h6 mb-0">{{ $asset->depreciation_duration }} months</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center mt-3">
                    <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-info mr-2 mb-2">
                        <i class="fas fa-edit mr-1"></i>{{ __('general_content.edit_trans_key') }}
                    </a>
                    <form method="POST" action="{{ route('assets.destroy', $asset->id) }}" class="d-inline mb-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash mr-1"></i>{{ __('general_content.delete_trans_key') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </x-adminlte-card>

    <div class="row">
        <div class="col-12 mb-4">
            <x-adminlte-card title="{{ __('general_content.accounting_trans_key') }}" theme="light" icon="fas fa-book">
                <ul class="list-group list-group-flush">
                    @forelse($asset->accountingEntries as $entry)
                        <li class="list-group-item d-flex flex-wrap justify-content-between">
                            <span class="font-weight-bold">{{ $entry->entry_label }}</span>
                            <span class="text-muted">{{ $entry->debit_amount }} / {{ $entry->credit_amount }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('general_content.no_data_trans_key') }}</li>
                    @endforelse
                </ul>
            </x-adminlte-card>
        </div>
        <div class="col-12 mb-4">
            <x-adminlte-card title="{{ __('general_content.gmao_maintenance_work_orders_trans_key') }}" theme="light" icon="fas fa-tools">
                <ul class="list-group list-group-flush">
                    @forelse($asset->workOrders as $workOrder)
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
                            <a href="{{ route('gmao.work-orders.show', $workOrder->id) }}" class="font-weight-bold">
                                #{{ $workOrder->id }} - {{ $workOrder->title }}
                            </a>
                            <span class="badge badge-info text-uppercase">
                                {{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('general_content.no_data_trans_key') }}</li>
                    @endforelse
                </ul>
                <div class="mt-3">
                    <a href="{{ route('gmao.work-orders.create', ['asset_id' => $asset->id]) }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> {{ __('general_content.gmao_new_work_order_trans_key') }}
                    </a>
                </div>
            </x-adminlte-card>
        </div>
        <div class="col-12">
            <x-adminlte-card title="{{ __('general_content.gmao_maintenance_plans_card_title_trans_key') }}" theme="light" icon="fas fa-clipboard-list">
                <ul class="list-group list-group-flush">
                    @forelse($asset->maintenancePlans as $plan)
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
                            <a href="{{ route('gmao.maintenance-plans.show', $plan->id) }}" class="font-weight-bold">
                                {{ $plan->title }}
                            </a>
                            <span class="badge badge-secondary text-uppercase">
                                {{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('general_content.no_data_trans_key') }}</li>
                    @endforelse
                </ul>
                <div class="mt-3">
                    <a href="{{ route('gmao.maintenance-plans.create', ['asset_id' => $asset->id]) }}" class="btn btn-secondary">
                        <i class="fas fa-plus mr-1"></i> {{ __('general_content.gmao_new_maintenance_plan_trans_key') }}
                    </a>
                </div>
            </x-adminlte-card>
        </div>
    </div>
@stop
