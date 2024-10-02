@extends('adminlte::page')

@section('title', __('general_content.risques_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.risques_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="row">
    <div class="col-md-8">
        <x-adminlte-card title="{{ __('general_content.risques_trans_key') }}" theme="info" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.section_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.severity_class_trans_key') }}</th>
                            <th>{{ __('general_content.probality_trans_key') }}</th>
                            <th>{{ __('general_content.preventive_measures_class_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($risks as $risks)
                        <tr>
                            <td>{{ $risks->section->label ?? 'N/A' }}</td>
                            <td>{{ $risks->description }}</td>
                            <td>{{ ['1' => __('general_content.low_trans_key'), '2' => __('general_content.moderate_trans_key'), '3' => __('general_content.high_trans_key')][$risks->severity] }}</td>
                            <td>{{ ['1' =>  __('general_content.rare_trans_key'), '2' =>  __('general_content.possible_trans_key'), '3' =>  __('general_content.probable_trans_key')][$risks->probability] }}</td>
                            <td>{{ $risks->preventive_measures }}</td>
                            <td><img src="{{  Avatar::create($risks->UserManagement['name'] ?? 'N/A')->toBase64() }}" /></td>
                            <td class=" py-0 align-middle">
                                <!-- Button Modal -->
                                <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#risks{{ $risks->id }}">
                                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                                </button>
                                <!-- Modal -->
                                <x-adminlte-modal id="risks{{ $risks->id }}" title="{{ __('Update Risk') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                    <form method="POST" action="{{ route('osh.risks.update', ['id' => $risks->id]) }}">
                                        @csrf
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
                                                <select class="form-control" name="section_id">
                                                    @foreach($sectionsSelect as $section)
                                                        <option value="{{ $section->id }}" {{ $risks->section_id == $section->id ? 'selected' : '' }}>{{ $section->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">{{ __('general_content.description_trans_key') }}</label>
                                                <textarea class="form-control" name="description" rows="3">{{ $risks->description }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="severity">{{ __('general_content.severity_class_trans_key') }}</label>
                                                <select class="form-control" name="severity">
                                                    <option value="1" {{ $risks->severity == 1 ? 'selected' : '' }}>{{ __('general_content.low_trans_key') }}</option>
                                                    <option value="2" {{ $risks->severity == 2 ? 'selected' : '' }}>{{ __('general_content.moderate_trans_key') }}</option>
                                                    <option value="3" {{ $risks->severity == 3 ? 'selected' : '' }}>{{ __('general_content.high_trans_key') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="probability">{{ __('general_content.probality_trans_key') }}</label>
                                                <select class="form-control" name="probability">
                                                    <option value="1" {{ $risks->probability == 1 ? 'selected' : '' }}>{{ __('general_content.rare_trans_key') }}</option>
                                                    <option value="2" {{ $risks->probability == 2 ? 'selected' : '' }}>{{ __('general_content.possible_trans_key') }}</option>
                                                    <option value="3" {{ $risks->probability == 3 ? 'selected' : '' }}>{{ __('general_content.probable_trans_key') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="preventive_measures">{{ __('general_content.preventive_measures_class_trans_key') }}</label>
                                                <textarea class="form-control" name="preventive_measures" rows="3">{{ $risks->preventive_measures }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                                                <select class="form-control" name="user_id">
                                                    @foreach($userSelect as $user)
                                                        <option value="{{ $user->id }}" {{ $risks->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
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
                        <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}" />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.section_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.severity_class_trans_key') }}</th>
                            <th>{{ __('general_content.probality_trans_key') }}</th>
                            <th>{{ __('general_content.preventive_measures_class_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
    </div>
    <div class="col-md-4">
        <form method="POST" action="{{ route('osh.risks.create') }}">
            @csrf
            <x-adminlte-card title="{{ __('general_content.new_risk_trans_key') }}" theme="secondary" maximizable>
                <div class="form-group">
                    <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
                    <select class="form-control" name="section_id">
                        @foreach($sectionsSelect as $section)
                            <option value="{{ $section->id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">{{ __('general_content.description_trans_key') }}</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="{{ __('general_content.description_trans_key') }}"></textarea>
                </div>
                <div class="form-group">
                    <label for="severity">{{ __('general_content.severity_class_trans_key') }}</label>
                    <select class="form-control" name="severity">
                        <option value="1">{{ __('general_content.low_trans_key') }}</option>
                        <option value="2">{{ __('general_content.moderate_trans_key') }}</option>
                        <option value="3">{{ __('general_content.high_trans_key') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="probability">{{ __('general_content.probality_trans_key') }}</label>
                    <select class="form-control" name="probability">
                        <option value="1">{{ __('general_content.rare_trans_key') }}</option>
                        <option value="2">{{ __('general_content.possible_trans_key') }}</option>
                        <option value="3">{{ __('general_content.probable_trans_key') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="preventive_measures">{{ __('general_content.preventive_measures_class_trans_key') }}</label>
                    <textarea class="form-control" name="preventive_measures" rows="3" placeholder="{{ __('general_content.preventive_measures_class_trans_key') }}"></textarea>
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