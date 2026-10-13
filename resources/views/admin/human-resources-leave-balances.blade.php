@extends('adminlte::page')

@section('title', __('general_content.leave_balances_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.leave_balances_trans_key') }} — {{ $PeriodLabel }}</h1>
@stop

@section('content')
@include('include.alert-result')

<div class="row">
    <div class="col-md-8">
        <form method="GET" action="{{ route('human.resources.leave.balances') }}" class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <label class="me-2 mb-0" for="period">{{ __('general_content.leave_period_trans_key') }}</label>
            <select class="form-select me-2 w-auto" name="period" id="period" onchange="this.form.submit()">
                @foreach($PeriodOptions as $value => $label)
                    <option value="{{ $value }}" @if($value === $SelectedPeriod) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-secondary rounded-0">{{ __('general_content.search_trans_key') }}</button></noscript>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <form method="POST" action="{{ route('human.resources.leave.balance.generate') }}">
            @csrf
            <input type="hidden" name="period_start" value="{{ $SelectedPeriod }}">
            <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.leave_generate_balances_trans_key') }}" theme="secondary" icon="fas fa-lg fa-magic"/>
        </form>
    </div>
</div>

<x-adminlte-card title="{{ __('general_content.leave_balances_trans_key') }}" theme="primary" maximizable>
    <div class="table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">{{ __('general_content.user_trans_key') }}</th>
                    @foreach($LeaveTypes as $type)
                        <th colspan="3" class="text-center border-start">
                            <span class="badge" style="background-color: {{ $type->color ?? '#6c757d' }}">&nbsp;</span>
                            {{ $type->label }}
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($LeaveTypes as $type)
                        <th class="text-end border-start"><small>{{ __('general_content.leave_acquired_trans_key') }}</small></th>
                        <th class="text-end"><small>{{ __('general_content.leave_taken_trans_key') }}</small></th>
                        <th class="text-end"><small>{{ __('general_content.leave_remaining_trans_key') }}</small></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse ($Rows as $row)
                <tr>
                    <td>
                        <a href="{{ route('human.resources.show.user', ['id' => $row['user']->id]) }}">{{ $row['user']->name }}</a>
                        @if($row['user']->job_title)
                            <br><small class="text-muted">{{ $row['user']->job_title }}</small>
                        @endif
                    </td>
                    @foreach($row['summary']['lines'] as $line)
                        <td class="text-end border-start">{{ number_format($line['acquired'], 2, ',', ' ') }}</td>
                        <td class="text-end">{{ number_format($line['taken'], 2, ',', ' ') }}</td>
                        <td class="text-end">
                            @if($line['remaining'] === null)
                                <span class="text-muted">—</span>
                            @else
                                <strong class="{{ $line['remaining'] < 0 ? 'text-danger' : '' }}">{{ number_format($line['remaining'], 2, ',', ' ') }}</strong>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <x-EmptyDataLine col="{{ 1 + ($LeaveTypes->count() * 3) }}" text="{{ __('general_content.no_data_trans_key') }}" />
            @endforelse
            </tbody>
        </table>
    </div>
    <small class="text-muted">
        {{ __('general_content.leave_period_trans_key') }} :
        {{ $PeriodStart->format('d/m/Y') }} → {{ $PeriodEnd->format('d/m/Y') }}
        — {{ __('general_content.leave_pending_hint_trans_key') }}
    </small>
</x-adminlte-card>
@stop

@section('css')
@stop

@section('js')
@stop
