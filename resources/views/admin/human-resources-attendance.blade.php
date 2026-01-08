@extends('adminlte::page')

@section('title', __('general_content.attendance_report_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.attendance_report_trans_key') }}</h1>
@stop

@section('content')
@include('include.alert-result')
<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('human.resources.attendance') }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                        <select class="form-control" name="user_id" id="user_id">
                            <option value="">{{ __('general_content.all_trans_key') }}</option>
                            @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}" @if($filters['user_id'] == $item->id) selected @endif>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date">{{ __('general_content.start_date_trans_key') }}</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" value="{{ $filters['start_date'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" value="{{ $filters['end_date'] }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.filter_trans_key') }}" theme="info" icon="fas fa-filter"/>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{ __('general_content.days_trans_key') }}</th>
                        <th>{{ __('general_content.total_duration_trans_key') }}</th>
                        <th>{{ __('general_content.anomalies_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($AttendanceReport as $row)
                        @php
                            $totalSeconds = $row['total_seconds'] ?? 0;
                            $hours = floor($totalSeconds / 3600);
                            $minutes = floor(($totalSeconds % 3600) / 60);
                            $duration = sprintf('%02d:%02d', $hours, $minutes);
                        @endphp
                        <tr>
                            <td>{{ optional($row['user'])->name }}</td>
                            <td>{{ $row['days'] ?? 0 }}</td>
                            <td>{{ $duration }}</td>
                            <td>{{ $row['anomalies'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}" />
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{ __('general_content.days_trans_key') }}</th>
                        <th>{{ __('general_content.total_duration_trans_key') }}</th>
                        <th>{{ __('general_content.anomalies_trans_key') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@stop
