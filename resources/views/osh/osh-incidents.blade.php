@extends('adminlte::page')

@section('title', __('general_content.incidents_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.incidents_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="row">
    <div class="col-md-8">
        <x-adminlte-card title="{{ __('general_content.incidents_trans_key') }}" theme="warning" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.incident_date_class_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.severity_class_trans_key') }}</th>
                            <th>{{ __('general_content.corrective_actions_class_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.resolution_time_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($incidents as $incident)
                        <tr>
                            <td>{{ $incident->incident_date }}</td>
                            <td>{{ $incident->description }}</td>
                            <td>{{ ['1' => __('general_content.minor_trans_key'), '2' => __('general_content.major_trans_key'), '3' => __('general_content.critical_trans_key')][$incident->severity] }}</td>
                            <td>{{ $incident->corrective_actions ?? 'N/A' }}</td>
                            <td>{{ ['1' =>  __('general_content.in_progress_trans_key'), '2' => __('general_content.solved_trans_key')][$incident->statut] }}</td>
                            <td>{{ $incident->resolution_date ?? 'N/A' }}</td>
                            <td><img src="{{  Avatar::create($incident->UserManagement['name'] ?? 'N/A')->toBase64() }}" /></td>
                            <td class="py-0 align-middle">
                                <!-- Button Modal -->
                                <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#incident{{ $incident->id }}">
                                    <i class="fa fa-lg fa-fw fa-edit"></i>
                                </button>
                                <!-- Modal -->
                                <x-adminlte-modal id="incident{{ $incident->id }}" title="Update {{ __('general_content.incidents_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                    <form method="POST" action="{{ route('osh.incidents.update', ['id' => $incident->id]) }}">
                                        @csrf
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="incident_date">{{ __('general_content.incident_date_class_trans_key') }}</label>
                                                <input type="date" class="form-control" name="incident_date" value="{{ $incident->incident_date }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="description">{{ __('general_content.description_trans_key') }}</label>
                                                <textarea class="form-control" name="description" rows="3">{{ $incident->description }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="severity">{{ __('general_content.severity_class_trans_key') }}</label>
                                                <select class="form-control" name="severity">
                                                    <option value="1" {{ $incident->severity == 1 ? 'selected' : '' }}>{{ __('general_content.minor_trans_key') }}</option>
                                                    <option value="2" {{ $incident->severity == 2 ? 'selected' : '' }}>{{ __('general_content.major_trans_key') }}</option>
                                                    <option value="3" {{ $incident->severity == 3 ? 'selected' : '' }}>{{ __('general_content.critical_trans_key') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="corrective_actions">{{ __('general_content.corrective_actions_class_trans_key') }}</label>
                                                <textarea class="form-control" name="corrective_actions" rows="3">{{ $incident->corrective_actions }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="statut">{{ __('general_content.status_trans_key') }}</label>
                                                <select class="form-control" name="statut">
                                                    <option value="1" {{ $incident->statut == 1 ? 'selected' : '' }}>{{ __('general_content.in_progress_trans_key') }}</option>
                                                    <option value="2" {{ $incident->statut == 2 ? 'selected' : '' }}>{{ __('general_content.solved_trans_key') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="resolution_date">{{ __('general_content.resolution_time_trans_key') }}</label>
                                                <input type="date" class="form-control" name="resolution_date" value="{{ $incident->resolution_date }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                                                <select class="form-control" name="user_id">
                                                    @foreach($userSelect as $user)
                                                        <option value="{{ $user->id }}" {{ $incident->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
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
                        <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}" />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.incident_date_class_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.severity_class_trans_key') }}</th>
                            <th>{{ __('general_content.corrective_actions_class_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.resolution_time_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
    </div>
    <div class="col-md-4">
        <form method="POST" action="{{ route('osh.incidents.create') }}">
            @csrf
            <x-adminlte-card title="{{ __('general_content.new_incident_trans_key') }}" theme="secondary" maximizable>
                <div class="form-group">
                    <label for="incident_date">{{ __('general_content.incident_date_class_trans_key') }}</label>
                    <input type="date" class="form-control" name="incident_date" required>
                </div>
                <div class="form-group">
                    <label for="description">{{ __('general_content.description_trans_key') }}</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="{{ __('general_content.description_trans_key') }}" required></textarea>
                </div>
                <div class="form-group">
                    <label for="severity">{{ __('general_content.severity_class_trans_key') }}</label>
                    <select class="form-control" name="severity">
                        <option value="1">{{ __('general_content.minor_trans_key') }}</option>
                        <option value="2">{{ __('general_content.major_trans_key') }}</option>
                        <option value="3">{{ __('general_content.critical_trans_key') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="corrective_actions">{{ __('general_content.corrective_actions_class_trans_key') }}</label>
                    <textarea class="form-control" name="corrective_actions" rows="3" placeholder="{{ __('general_content.corrective_actions_class_trans_key') }}"></textarea>
                </div>
                <div class="form-group">
                    <label for="resolution_date">{{ __('general_content.resolution_time_trans_key') }}</label>
                    <input type="date" class="form-control" name="resolution_date">
                </div>
                <div class="form-group">
                    @include('include.form.form-select-user',['userId' =>  null ])
                </div>
                <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
            </x-adminlte-card>
        </form>
    </div>
</div>

@stop

@section('css')
@stop

@section('js')
@stop