@extends('adminlte::page')

@section('title', __('general_content.formations_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.formations_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="row">
    <div class="col-md-8">
        <x-adminlte-card title="{{ __('general_content.osh_training_List_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th>{{ __('general_content.type_of_training_trans_key') }}</th>
                            <th>{{ __('general_content.training_date_trans_key') }}</th>
                            <th>{{ __('general_content.expiration_date_trans_key') }}</th>
                            <th>{{ __('general_content.certification_obtained_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trainings as $training)
                        <tr>
                            <td><img src="{{  Avatar::create($training->UserManagement['name'] ?? 'N/A')->toBase64() }}" alt="User Avatar"/></td>
                            <td>{{ $training->type_of_training }}</td>
                            <td>{{ $training->training_date }}</td>
                            <td>{{ $training->expiration_date }}</td>
                            <td>{{ $training->certification_obtained == 1 ? __('general_content.yes_trans_key') : __('general_content.no_trans_key') }}</td>
                            <td class="py-0 align-middle">
                                <!-- Button Modal -->
                                <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#trainingModal{{ $training->id }}">
                                    <i class="fa fa-lg fa-fw fa-edit"></i>
                                </button>

                                <!-- Modal -->
                                <x-adminlte-modal id="trainingModal{{ $training->id }}" title="Update {{ $training->type_of_training }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                    <form method="POST" action="{{ route('osh.training.update', ['id' => $training->id]) }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="type_of_training">{{ __('general_content.type_of_training_trans_key') }}</label>
                                                <input type="text" class="form-control" name="type_of_training" id="type_of_training" value="{{ $training->type_of_training }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="training_date">{{ __('general_content.training_date_trans_key') }}</label>
                                                <input type="date" class="form-control" name="training_date" id="training_date" value="{{ $training->training_date }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="expiration_date">{{ __('general_content.expiration_date_trans_key') }}</label>
                                                <input type="date" class="form-control" name="expiration_date" id="expiration_date" value="{{ $training->expiration_date }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="certification_obtained-{{ $training->id }}">{{ __('general_content.certification_obtained_trans_key') }}</label>
                                                <select class="form-control" name="certification_obtained" id="certification_obtained-{{ $training->id }}">
                                                    <option value="1" {{ $training->certification_obtained == 1 ? 'selected' : '' }}>{{ __('general_content.yes_trans_key') }}</option>
                                                    <option value="2" {{ $training->certification_obtained == 2 ? 'selected' : '' }}>{{ __('general_content.no_trans_key') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                                                <select class="form-control" name="user_id" id="user_id">
                                                    @foreach ($userSelect as $user)
                                                    <option value="{{ $user->id }}" {{ $training->user_id == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
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
                        <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th>{{ __('general_content.type_of_training_trans_key') }}</th>
                            <th>{{ __('general_content.training_date_trans_key') }}</th>
                            <th>{{ __('general_content.expiration_date_trans_key') }}</th>
                            <th>{{ __('general_content.certification_obtained_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
    </div>

    <div class="col-md-4">
        <form method="POST" action="{{ route('osh.training.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_training_trans_key') }}" theme="secondary" maximizable>
                @csrf
                <div class="form-group">
                    <label for="type_of_training">{{ __('general_content.type_of_training_trans_key') }}</label>
                    <input type="text" class="form-control" name="type_of_training" id="type_of_training" placeholder="{{ __('general_content.type_of_training_trans_key') }}">
                </div>
                <div class="form-group">
                    <label for="training_date">{{ __('general_content.training_date_trans_key') }}</label>
                    <input type="date" class="form-control" name="training_date" id="training_date">
                </div>
                <div class="form-group">
                    <label for="expiration_date">{{ __('general_content.expiration_date_trans_key') }}</label>
                    <input type="date" class="form-control" name="expiration_date" id="expiration_date">
                </div>
                <div class="form-group">
                    <label for="certification_obtained" class="col-form-label">{{ __('general_content.certification_obtained_trans_key') }}</label>
                    <x-adminlte-input-switch name="certification_obtained" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}"
                    data-on-color="teal" checked/>
                </div>
                <div class="form-group">
                    @include('include.form.form-select-user', ['userId' => null])
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