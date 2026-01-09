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
        <ul class="nav nav-tabs" id="attendance-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="attendance-activities-tab" data-toggle="tab" href="#attendance-activities" role="tab" aria-controls="attendance-activities" aria-selected="true">
                    {{ __('general_content.activities_trans_key') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="attendance-punches-tab" data-toggle="tab" href="#attendance-punches" role="tab" aria-controls="attendance-punches" aria-selected="false">
                    {{ __('general_content.attendance_trans_key') }}
                </a>
            </li>
        </ul>

        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="attendance-activities" role="tabpanel" aria-labelledby="attendance-activities-tab">
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

            <div class="tab-pane fade" id="attendance-punches" role="tabpanel" aria-labelledby="attendance-punches-tab">
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
                            @forelse ($AttendancePunchReport as $row)
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
    </div>
</div>
@stop
