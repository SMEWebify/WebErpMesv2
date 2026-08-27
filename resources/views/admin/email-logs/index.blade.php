@extends('adminlte::page')

@section('title', __('general_content.email_logs_trans_key'))

@section('content_header')
    <h1><i class="fas fa-history mr-1"></i> {{ __('general_content.email_logs_trans_key') }}</h1>
@stop

@section('content')

    @if(session('success'))
        <x-adminlte-alert theme="success" dismissable>{{ session('success') }}</x-adminlte-alert>
    @endif
    @if($errors->any())
        <x-adminlte-alert theme="danger" dismissable>
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ $counts['sent'] }}</h3><p>{{ __('general_content.email_status_sent_trans_key') }}</p></div>
                <div class="icon"><i class="fas fa-check"></i></div>
                <a href="{{ route('admin.email-logs.index', ['status' => 'sent']) }}" class="small-box-footer">
                    {{ __('general_content.filter_trans_key') }} <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ $counts['failed'] }}</h3><p>{{ __('general_content.email_status_failed_trans_key') }}</p></div>
                <div class="icon"><i class="fas fa-times"></i></div>
                <a href="{{ route('admin.email-logs.index', ['status' => 'failed']) }}" class="small-box-footer">
                    {{ __('general_content.filter_trans_key') }} <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ $counts['pending'] }}</h3><p>{{ __('general_content.email_status_pending_trans_key') }}</p></div>
                <div class="icon"><i class="fas fa-clock"></i></div>
                <a href="{{ route('admin.email-logs.index', ['status' => 'pending']) }}" class="small-box-footer">
                    {{ __('general_content.filter_trans_key') }} <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <x-adminlte-card theme="secondary" theme-mode="outline">
        <form method="GET" class="form-inline mb-3">
            <input type="text" name="q" value="{{ $search }}" class="form-control mr-2"
                   placeholder="{{ __('general_content.search_by_recipient_or_subject_trans_key') }}">
            <select name="status" class="form-control mr-2">
                <option value="">{{ __('general_content.all_trans_key') }}</option>
                <option value="sent"    @selected($status === 'sent')>{{ __('general_content.email_status_sent_trans_key') }}</option>
                <option value="failed"  @selected($status === 'failed')>{{ __('general_content.email_status_failed_trans_key') }}</option>
                <option value="pending" @selected($status === 'pending')>{{ __('general_content.email_status_pending_trans_key') }}</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            @if($status || $search)
                <a href="{{ route('admin.email-logs.index') }}" class="btn btn-outline-secondary ml-2">
                    {{ __('general_content.reset_trans_key') }}
                </a>
            @endif
        </form>

        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>{{ __('general_content.date_trans_key') }}</th>
                    <th>{{ __('general_content.to_trans_key') }}</th>
                    <th>{{ __('general_content.object_trans_key') }}</th>
                    <th>{{ __('general_content.document_trans_key') }}</th>
                    <th>{{ __('general_content.by_trans_key') }}</th>
                    <th>{{ __('general_content.status_trans_key') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->to }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($log->subject, 50) }}</td>
                        <td>
                            @if($log->emailable_type)
                                <small class="text-muted">{{ class_basename($log->emailable_type) }} #{{ $log->emailable_id }}</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->sender->name ?? '—' }}</td>
                        <td>
                            @switch($log->status)
                                @case('sent')
                                    <span class="badge badge-success" title="{{ $log->sent_at?->format('d/m/Y H:i') }}">
                                        <i class="fas fa-check"></i> {{ __('general_content.email_status_sent_trans_key') }}
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="badge badge-danger" title="{{ $log->error }}">
                                        <i class="fas fa-times"></i> {{ __('general_content.email_status_failed_trans_key') }}
                                    </span>
                                    @break
                                @default
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-clock"></i> {{ __('general_content.email_status_pending_trans_key') }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.email-logs.show', $log) }}" class="btn btn-sm btn-outline-info" title="{{ __('general_content.details_trans_key') }}">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($log->status === 'failed' && $log->emailable_type)
                                <form action="{{ route('admin.email-logs.resend', $log) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                            title="{{ __('general_content.email_resend_trans_key') }}">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('general_content.no_data_trans_key') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $logs->links() }}
    </x-adminlte-card>
@stop
