@extends('adminlte::page')

@section('title', __('general_content.email_log_detail_trans_key'))

@section('content_header')
    <h1>
        <i class="fas fa-envelope-open-text mr-1"></i> {{ __('general_content.email_log_detail_trans_key') }}
    </h1>
@stop

@section('content')

    @if($errors->any())
        <x-adminlte-alert theme="danger" dismissable>
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    <div class="row">
        <div class="col-md-8">
            <x-adminlte-card title="{{ $log->subject }}" theme="primary" theme-mode="outline">
                <dl class="row">
                    <dt class="col-sm-3">{{ __('general_content.to_trans_key') }}</dt>
                    <dd class="col-sm-9">{{ $log->to }}</dd>

                    <dt class="col-sm-3">{{ __('general_content.date_trans_key') }}</dt>
                    <dd class="col-sm-9">{{ $log->created_at?->format('d/m/Y H:i:s') }}</dd>

                    <dt class="col-sm-3">{{ __('general_content.by_trans_key') }}</dt>
                    <dd class="col-sm-9">{{ $log->sender->name ?? '—' }}</dd>

                    @if($log->emailable_type)
                        <dt class="col-sm-3">{{ __('general_content.document_trans_key') }}</dt>
                        <dd class="col-sm-9">{{ class_basename($log->emailable_type) }} #{{ $log->emailable_id }}</dd>
                    @endif

                    <dt class="col-sm-3">{{ __('general_content.status_trans_key') }}</dt>
                    <dd class="col-sm-9">
                        @switch($log->status)
                            @case('sent')
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> {{ __('general_content.email_status_sent_trans_key') }}
                                </span>
                                @if($log->sent_at) — {{ $log->sent_at->format('d/m/Y H:i:s') }} @endif
                                @break
                            @case('failed')
                                <span class="badge badge-danger">
                                    <i class="fas fa-times"></i> {{ __('general_content.email_status_failed_trans_key') }}
                                </span>
                                @break
                            @default
                                <span class="badge badge-secondary">
                                    <i class="fas fa-clock"></i> {{ __('general_content.email_status_pending_trans_key') }}
                                </span>
                        @endswitch
                    </dd>

                    @if($log->attachment)
                        <dt class="col-sm-3">{{ __('general_content.attachment_trans_key') }}</dt>
                        <dd class="col-sm-9">
                            {{ $log->attachment_original_name ?? basename($log->attachment) }}
                        </dd>
                    @endif
                </dl>

                <hr>
                <h5>{{ __('general_content.message_trans_key') }}</h5>
                <div class="border rounded p-3 bg-light">
                    {!! $log->message !!}
                </div>

                @if($log->status === 'failed')
                    <hr>
                    <h5 class="text-danger">{{ __('general_content.error_trans_key') }}</h5>
                    <pre class="bg-light p-3 rounded" style="white-space: pre-wrap;">{{ $log->error }}</pre>
                @endif
            </x-adminlte-card>
        </div>

        <div class="col-md-4">
            <x-adminlte-card title="{{ __('general_content.actions_trans_key') }}" theme="info" theme-mode="outline">
                <a href="{{ route('admin.email-logs.index') }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-arrow-left"></i> {{ __('general_content.back_trans_key') }}
                </a>
                @if($log->status === 'failed' && $log->emailable_type)
                    <form action="{{ route('admin.email-logs.resend', $log) }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="fas fa-redo"></i> {{ __('general_content.email_resend_trans_key') }}
                        </button>
                    </form>
                @endif
            </x-adminlte-card>
        </div>
    </div>
@stop
