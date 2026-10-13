@extends('adminlte::page')

@section('title', __('general_content.gmao_maintenance_plan_number_trans_key', ['id' => $plan->id]))

@section('content_header')
    <h1>Maintenance Plan #{{ $plan->id }}</h1>
@stop


@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cog text-muted me-2"></i>
                            <h5 class="mb-0 fw-bold">{{ __('general_content.gmao_general_information_trans_key') }}</h5>
                        </div>
                        <span class="badge text-bg-success px-3 py-2">{{ __('general_content.gmao_active_trans_key') }}</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.asset_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->asset?->name ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_frequency_trans_key') }}</div>
                            <div class="fw-bold">{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.title_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->title }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_estimated_duration_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->estimated_duration_minutes ? $plan->estimated_duration_minutes . ' ' . __('general_content.gmao_minutes_suffix_trans_key') : __('general_content.not_available_trans_key') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-uppercase text-muted small">{{ __('general_content.description_trans_key') }}</div>
                            <div>{{ $plan->description ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-bolt text-muted me-2"></i>
                        <h5 class="mb-0 fw-bold">{{ __('general_content.gmao_trigger_configuration_trans_key') }}</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_trigger_type_trans_key') }}</div>
                            <div class="fw-bold">{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_trigger_value_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->trigger_value ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="far fa-calendar-alt text-muted me-2"></i>
                        <h5 class="mb-0 fw-bold">{{ __('general_content.gmao_schedule_information_trans_key') }}</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_fixed_date_trans_key') }}</div>
                            <div class="fw-bold">{{ optional($plan->fixed_date)->format('Y-m-d') ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_next_scheduled_date_trans_key') }}</div>
                            <div class="fw-bold">{{ optional($plan->fixed_date)->format('Y-m-d') ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.created_at_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->created_at?->format('Y-m-d') ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_last_modified_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->updated_at?->format('Y-m-d') ?? __('general_content.not_available_trans_key') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-tools text-muted me-2"></i>
                        <h5 class="mb-0 fw-bold">{{ __('general_content.gmao_execution_resources_trans_key') }}</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_required_skill_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->required_skill ?? __('general_content.gmao_not_specified_trans_key') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_required_parts_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->required_parts ?? __('general_content.gmao_not_specified_trans_key') }}</div>
                        </div>
                        <div class="col-12 mb-0">
                            <div class="text-uppercase text-muted small">{{ __('general_content.gmao_actions_list_trans_key') }}</div>
                            <div class="fw-bold">{{ $plan->actions ?? __('general_content.gmao_no_actions_defined_trans_key') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-3">{{ __('general_content.action_trans_key') }}</div>
                    <form method="POST" action="{{ route('gmao.maintenance-plans.generate-work-order', $plan->id) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-bolt me-1"></i> {{ __('general_content.gmao_generate_work_order_trans_key') }}
                        </button>
                    </form>
                    <a href="{{ route('gmao.maintenance-plans.edit', $plan->id) }}" class="btn btn-info w-100 mb-2">
                        <i class="fas fa-edit me-1"></i> {{ __('general_content.gmao_edit_plan_trans_key') }}
                    </a>
                    <a href="{{ route('gmao.maintenance-plans.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('general_content.gmao_back_to_list_trans_key') }}
                    </a>
                    <form method="POST" action="{{ route('gmao.maintenance-plans.destroy', $plan->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash-alt me-1"></i> {{ __('general_content.delete_trans_key') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-3">{{ __('general_content.gmao_plan_summary_trans_key') }}</div>
                    <div class="mb-3">
                        <div class="text-muted small">{{ __('general_content.status_trans_key') }}</div>
                        <div class="fw-bold text-success">{{ __('general_content.gmao_active_trans_key') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">{{ __('general_content.gmao_frequency_trans_key') }}</div>
                        <div class="fw-bold">{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">{{ __('general_content.gmao_duration_trans_key') }}</div>
                        <div class="fw-bold">{{ $plan->estimated_duration_minutes ? $plan->estimated_duration_minutes . ' ' . __('general_content.gmao_minutes_suffix_trans_key') : __('general_content.not_available_trans_key') }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('general_content.gmao_next_scheduled_trans_key') }}</div>
                        <div class="fw-bold">{{ optional($plan->fixed_date)->format('Y-m-d') ?? __('general_content.not_available_trans_key') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
