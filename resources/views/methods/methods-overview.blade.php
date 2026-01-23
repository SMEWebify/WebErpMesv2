@extends('adminlte::page')

@section('title', __('general_content.methods_overview_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.methods_overview_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
    @include('include.alert-result')

    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="{{ __('general_content.service_trans_key') }}" theme="info" maximizable>
                <div class="row">
                    @forelse ($services as $service)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-bold">{{ $service->label }}</div>
                                        <small class="text-muted">{{ $service->code }}</small>
                                    </div>
                                    <span class="badge text-white" style="background-color: {{ $service->color }};">&nbsp;</span>
                                </div>
                                <div class="mt-2">
                                    <div><strong>{{ __('general_content.ressource_trans_key') }}</strong> : {{ $service->ressources_count }}</div>
                                    <div><strong>{{ __('general_content.in_progress_trans_key') }}</strong> : {{ $service->tasks_in_progress_count }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}" />
                        </div>
                    @endforelse
                </div>
            </x-adminlte-card>
        </div>
    </div>

    <div class="row">
        @forelse ($sections as $section)
            <div class="col-12">
                <x-adminlte-card title="{{ $section->label }}" theme="primary" maximizable>
                    <div class="row">
                        @forelse ($section->Ressources as $resource)
                            <div class="col-lg-6">
                                <div class="border rounded p-3 mb-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">{{ $resource->label }}</h5>
                                            <small class="text-muted">
                                                {{ __('general_content.service_trans_key') }} :
                                                {{ $resource->service?->label ?? __('general_content.no_data_trans_key') }}
                                            </small>
                                        </div>
                                        <span class="badge badge-light">
                                            {{ __('general_content.location_trans_key') }} : {{ $resource->locations->count() }}
                                        </span>
                                    </div>

                                    <div class="mt-3">
                                        <strong>{{ __('general_content.location_in_workshop_trans_key') }}</strong>
                                        <div class="d-flex flex-wrap mt-2">
                                            @forelse ($resource->locations as $location)
                                                <span class="badge badge-pill text-white mr-2 mb-2" style="background-color: {{ $location->color }};">
                                                    {{ $location->label }}
                                                </span>
                                            @empty
                                                <span class="text-muted">{{ __('general_content.no_data_trans_key') }}</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <strong>{{ __('general_content.tasks_trans_key') }} - {{ __('general_content.in_progress_trans_key') }}</strong>
                                        <div class="mt-2">
                                            @forelse ($resource->tasks as $task)
                                                <div class="border rounded p-2 mb-2">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <div class="font-weight-bold">{{ $task->code }} - {{ $task->label }}</div>
                                                            @if ($task->Products)
                                                                <small class="text-muted">{{ $task->Products->label }}</small>
                                                            @endif
                                                        </div>
                                                        <span class="badge badge-info">{{ $task->status->title ?? __('general_content.in_progress_trans_key') }}</span>
                                                    </div>
                                                    <div class="mt-2">
                                                        <x-adminlte-progress theme="info" value="{{ $task->progress() }}" with-label animated/>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted mb-0">{{ __('general_content.no_task_trans_key') }}</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}" />
                            </div>
                        @endforelse
                    </div>
                </x-adminlte-card>
            </div>
        @empty
            <div class="col-12">
                <x-EmptyDataLine col="12" text="{{ __('general_content.no_section_trans_key') }}" />
            </div>
        @endforelse
    </div>
@stop

@section('css')
@stop

@section('js')
@stop
