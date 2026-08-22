@extends('adminlte::page')

@section('title', __('general_content.skills_matrix_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.skills_matrix_trans_key') }}</h1>
@stop

@section('content')
@include('include.alert-result')

@php
    use App\Services\HumanResources\HabilitationService;

    $badges = [
        HabilitationService::STATUS_VALID    => ['badge-success', 'fas fa-check', 'skills_status_valid_trans_key'],
        HabilitationService::STATUS_EXPIRING => ['badge-warning', 'fas fa-hourglass-half', 'skills_status_expiring_trans_key'],
        HabilitationService::STATUS_EXPIRED  => ['badge-danger',  'fas fa-times', 'skills_status_expired_trans_key'],
        HabilitationService::STATUS_FAILED   => ['badge-secondary', 'fas fa-ban', 'skills_status_failed_trans_key'],
        HabilitationService::STATUS_MISSING  => ['badge-light', 'fas fa-minus', 'skills_status_missing_trans_key'],
    ];
@endphp

<div class="callout callout-info">
    <i class="fas fa-info-circle"></i>
    {{ __('general_content.skills_matrix_advisory_trans_key') }}
</div>

<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#Matrix" data-toggle="tab">{{ __('general_content.skills_matrix_trans_key') }}</a></li>
            <li class="nav-item">
                <a class="nav-link" href="#Alerts" data-toggle="tab">
                    {{ __('general_content.skills_alerts_trans_key') }}
                    @if(count($Alerts) > 0)<span class="badge badge-warning">{{ count($Alerts) }}</span>@endif
                </a>
            </li>
            <li class="nav-item"><a class="nav-link" href="#Types" data-toggle="tab">{{ __('general_content.training_types_trans_key') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            <div class="tab-pane active" id="Matrix">
                <div class="table-responsive p-0">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.user_trans_key') }}</th>
                                @foreach($TrainingTypes as $Type)
                                    <th class="text-center">
                                        <span class="badge" style="background-color: {{ $Type->color ?? '#6c757d' }}">&nbsp;</span>
                                        {{ $Type->code }}
                                        @if($Type->resources->isNotEmpty())
                                            <br><small class="text-muted">{{ $Type->resources->pluck('code')->implode(', ') }}</small>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($Users as $User)
                            <tr>
                                <td>
                                    {{ $User->name }}
                                    @if($User->job_title)<br><small class="text-muted">{{ $User->job_title }}</small>@endif
                                </td>
                                @foreach($TrainingTypes as $Type)
                                    @php
                                        $cell = $Matrix[$User->id][$Type->id] ?? ['status' => HabilitationService::STATUS_MISSING, 'expires_at' => null];
                                        [$class, $icon, $labelKey] = $badges[$cell['status']];
                                    @endphp
                                    <td class="text-center">
                                        <span class="badge {{ $class }}" title="{{ __('general_content.' . $labelKey) }}">
                                            <i class="{{ $icon }}"></i>
                                        </span>
                                        @if($cell['expires_at'])
                                            <br><small class="text-muted">{{ $cell['expires_at']->format('d/m/Y') }}</small>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <x-EmptyDataLine col="{{ 1 + $TrainingTypes->count() }}" text="{{ __('general_content.no_data_trans_key') }}" />
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">
                    @foreach($badges as $status => [$class, $icon, $labelKey])
                        <span class="badge {{ $class }}"><i class="{{ $icon }}"></i></span> {{ __('general_content.' . $labelKey) }}&nbsp;&nbsp;
                    @endforeach
                    — {{ __('general_content.skills_warning_window_trans_key', ['days' => $WarningDays]) }}
                </small>
            </div>

            <div class="tab-pane" id="Alerts">
                <div class="table-responsive p-0">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.task_trans_key') }}</th>
                                <th>{{ __('general_content.user_trans_key') }}</th>
                                <th>{{ __('general_content.ressource_trans_key') }}</th>
                                <th>{{ __('general_content.training_type_trans_key') }}</th>
                                <th>{{ __('general_content.status_trans_key') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($Alerts as $Alert)
                            @php [$class, $icon, $labelKey] = $badges[$Alert['status']]; @endphp
                            <tr>
                                <td>{{ $Alert['task']->code }} — {{ $Alert['task']->label }}</td>
                                <td>{{ $Alert['user']->name ?? '' }}</td>
                                <td>{{ $Alert['resource']->code }} {{ $Alert['resource']->label }}</td>
                                <td>{{ $Alert['type']->label }}</td>
                                <td><span class="badge {{ $class }}">{{ __('general_content.' . $labelKey) }}</span></td>
                            </tr>
                        @empty
                            <x-EmptyDataLine col="5" text="{{ __('general_content.skills_no_alert_trans_key') }}" />
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane" id="Types">
                <div class="row">
                    <div class="col-md-7">
                        <x-adminlte-card title="{{ __('general_content.training_types_trans_key') }}" theme="primary" maximizable>
                            <div class="table-responsive p-0">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('general_content.code_trans_key') }}</th>
                                            <th>{{ __('general_content.label_trans_key') }}</th>
                                            <th>{{ __('general_content.ressources_trans_key') }}</th>
                                            <th class="text-right">{{ __('general_content.training_validity_trans_key') }}</th>
                                            <th>{{ __('general_content.status_trans_key') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($AllTrainingTypes as $Type)
                                        <tr>
                                            <td>
                                                <span class="badge" style="background-color: {{ $Type->color ?? '#6c757d' }}">&nbsp;</span>
                                                {{ $Type->code }}
                                            </td>
                                            <td>{{ $Type->label }}</td>
                                            <td><small>{{ $Type->resources->pluck('code')->implode(', ') ?: '—' }}</small></td>
                                            <td class="text-right">{{ $Type->validity_months ? $Type->validity_months : '∞' }}</td>
                                            <td>
                                                @if($Type->active)
                                                    {{ __('general_content.active_trans_key') }}
                                                @else
                                                    <span class="text-muted">{{ __('general_content.inactive_trans_key') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-0 align-middle">
                                                <x-ButtonTextEdit :modalTarget="'TrainingType' . $Type->id" />
                                                <x-adminlte-modal id="TrainingType{{ $Type->id }}" title="{{ $Type->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                                    <form method="POST" action="{{ route('osh.training.type.update', ['id' => $Type->id]) }}">
                                                        @csrf
                                                        <div class="card-body">
                                                            <div class="form-group">
                                                                <label>{{ __('general_content.code_trans_key') }}</label>
                                                                <input type="text" class="form-control" name="code" maxlength="30" value="{{ $Type->code }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('general_content.label_trans_key') }}</label>
                                                                <input type="text" class="form-control" name="label" value="{{ $Type->label }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('general_content.color_trans_key') }}</label>
                                                                <input type="color" class="form-control" name="color" value="{{ $Type->color ?? '#6c757d' }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('general_content.training_validity_trans_key') }}</label>
                                                                <input type="number" min="0" max="600" class="form-control" name="validity_months" value="{{ $Type->validity_months }}">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>{{ __('general_content.ressources_trans_key') }}</label>
                                                                <select class="form-control" name="resources[]" multiple size="6">
                                                                    @foreach($Resources as $Resource)
                                                                        <option value="{{ $Resource->id }}" @if($Type->resources->contains($Resource->id)) selected @endif>{{ $Resource->code }} — {{ $Resource->label }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted">{{ __('general_content.skills_resources_hint_trans_key') }}</small>
                                                            </div>
                                                            <div class="form-check">
                                                                <input type="hidden" name="active" value="0">
                                                                <input type="checkbox" class="form-check-input" name="active" value="1" id="typeActive{{ $Type->id }}" @if($Type->active) checked @endif>
                                                                <label class="form-check-label" for="typeActive{{ $Type->id }}">{{ __('general_content.active_trans_key') }}</label>
                                                            </div>
                                                        </div>
                                                        <div class="card-footer">
                                                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                                        </div>
                                                    </form>
                                                </x-adminlte-modal>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-adminlte-card>
                    </div>
                    <div class="col-md-5">
                        <x-adminlte-card title="{{ __('general_content.training_new_type_trans_key') }}" theme="secondary" maximizable>
                            <form method="POST" action="{{ route('osh.training.type.store') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="code">{{ __('general_content.code_trans_key') }}</label>
                                    <input type="text" class="form-control" name="code" id="code" maxlength="30" required>
                                </div>
                                <div class="form-group">
                                    <label for="label">{{ __('general_content.label_trans_key') }}</label>
                                    <input type="text" class="form-control" name="label" id="label" required>
                                </div>
                                <div class="form-group">
                                    <label for="color">{{ __('general_content.color_trans_key') }}</label>
                                    <input type="color" class="form-control" name="color" id="color" value="#6c757d">
                                </div>
                                <div class="form-group">
                                    <label for="validity_months">{{ __('general_content.training_validity_trans_key') }}</label>
                                    <input type="number" min="0" max="600" class="form-control" name="validity_months" id="validity_months" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="resources">{{ __('general_content.ressources_trans_key') }}</label>
                                    <select class="form-control" name="resources[]" id="resources" multiple size="6">
                                        @foreach($Resources as $Resource)
                                            <option value="{{ $Resource->id }}">{{ $Resource->code }} — {{ $Resource->label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">{{ __('general_content.skills_resources_hint_trans_key') }}</small>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </div>
                            </form>
                        </x-adminlte-card>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
