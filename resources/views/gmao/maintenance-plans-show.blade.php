@extends('adminlte::page')

@section('title', 'Maintenance Plan #' . $plan->id)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cog text-muted mr-2"></i>
                            <h5 class="mb-0 font-weight-bold">General Information</h5>
                        </div>
                        <span class="badge badge-success px-3 py-2">Active</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Asset</div>
                            <div class="font-weight-bold">{{ $plan->asset?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Frequency</div>
                            <div class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Title</div>
                            <div class="font-weight-bold">{{ $plan->title }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Estimated Duration</div>
                            <div class="font-weight-bold">{{ $plan->estimated_duration_minutes ? $plan->estimated_duration_minutes . ' min' : 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-uppercase text-muted small">Description</div>
                            <div>{{ $plan->description ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-bolt text-muted mr-2"></i>
                        <h5 class="mb-0 font-weight-bold">Trigger Configuration</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Trigger Type</div>
                            <div class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Trigger Value</div>
                            <div class="font-weight-bold">{{ $plan->trigger_value ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="far fa-calendar-alt text-muted mr-2"></i>
                        <h5 class="mb-0 font-weight-bold">Schedule Information</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Fixed Date</div>
                            <div class="font-weight-bold">{{ optional($plan->fixed_date)->format('Y-m-d') ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Next Scheduled Date</div>
                            <div class="font-weight-bold">{{ optional($plan->fixed_date)->format('Y-m-d') ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Created At</div>
                            <div class="font-weight-bold">{{ $plan->created_at?->format('Y-m-d') ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Last Modified</div>
                            <div class="font-weight-bold">{{ $plan->updated_at?->format('Y-m-d') ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-tools text-muted mr-2"></i>
                        <h5 class="mb-0 font-weight-bold">Execution Resources</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Required Skill</div>
                            <div class="font-weight-bold">{{ $plan->required_skill ?? 'Not specified' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-uppercase text-muted small">Required Parts</div>
                            <div class="font-weight-bold">{{ $plan->required_parts ?? 'Not specified' }}</div>
                        </div>
                        <div class="col-12 mb-0">
                            <div class="text-uppercase text-muted small">Actions List</div>
                            <div class="font-weight-bold">{{ $plan->actions ?? 'No actions defined' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-3">Actions</div>
                    <form method="POST" action="{{ route('gmao.maintenance-plans.generate-work-order', $plan->id) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-bolt mr-1"></i> Generate Work Order
                        </button>
                    </form>
                    <a href="{{ route('gmao.maintenance-plans.edit', $plan->id) }}" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-edit mr-1"></i> Edit Plan
                    </a>
                    <a href="{{ route('gmao.maintenance-plans.index') }}" class="btn btn-outline-secondary btn-block mb-2">
                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                    </a>
                    <form method="POST" action="{{ route('gmao.maintenance-plans.destroy', $plan->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-trash-alt mr-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-3">Plan Summary</div>
                    <div class="mb-3">
                        <div class="text-muted small">Status</div>
                        <div class="font-weight-bold text-success">Active</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Frequency</div>
                        <div class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Duration</div>
                        <div class="font-weight-bold">{{ $plan->estimated_duration_minutes ? $plan->estimated_duration_minutes . ' min' : 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Next Scheduled</div>
                        <div class="font-weight-bold">{{ optional($plan->fixed_date)->format('Y-m-d') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
