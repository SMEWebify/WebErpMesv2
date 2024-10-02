@extends('adminlte::page')

@section('title', __('general_content.conformites_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.conformites_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="row">
    <div class="col-md-8">
        <x-adminlte-card title="{{ __('general_content.conformites_trans_key') }}" theme="success" maximizable>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.document_type_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.expiration_date_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.section_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($conformities as $conformite)
                        <tr>
                            <td>{{ $conformite->document_type }}</td>
                            <td>{{ $conformite->description ?? 'N/A' }}</td>
                            <td>{{ $conformite->expiration_date ?? 'N/A' }}</td>
                            <td>{{ [
                                    '1' => __('general_content.in_progress_trans_key') ,
                                    '2' => __('general_content.approved_trans_key'), 
                                    '3' => __('general_content.expired_trans_key')
                                    ]
                                    [$conformite->statut]
                                }}
                            </td>
                            <td>{{ $conformite->section->label ?? 'N/A' }}</td>
                            <td><img src="{{ Avatar::create($conformite->UserManagement['name'] ?? 'N/A')->toBase64() }}" /></td>
                            <td class="py-0 align-middle">
                                <!-- Button Modal -->
                                <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#conformite{{ $conformite->id }}">
                                    <i class="fa fa-lg fa-fw fa-edit"></i>
                                </button>
                                <!-- Modal -->
                                <x-adminlte-modal id="conformite{{ $conformite->id }}" title="Update {{ __('general_content.conformites_trans_key') }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                    <form method="POST" action="{{ route('osh.conformities.update', ['id' => $conformite->id]) }}">
                                        @csrf
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="document_type">{{ __('general_content.document_type_trans_key') }}</label>
                                                <input type="text" class="form-control" name="document_type" value="{{ $conformite->document_type }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="description">{{ __('general_content.description_trans_key') }}</label>
                                                <textarea class="form-control" name="description" rows="3">{{ $conformite->description }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="expiration_date">{{ __('general_content.expiration_date_trans_key') }}</label>
                                                <input type="date" class="form-control" name="expiration_date" value="{{ $conformite->expiration_date }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="statut">{{ __('general_content.status_trans_key') }}</label>
                                                <select class="form-control" name="statut">
                                                    <option value="1" {{ $conformite->statut == 1 ? 'selected' : '' }}>{{ __('general_content.in_progress_trans_key') }}</option>
                                                    <option value="2" {{ $conformite->statut == 2 ? 'selected' : '' }}>{{ __('general_content.approved_trans_key') }}</option>
                                                    <option value="3" {{ $conformite->statut == 3 ? 'selected' : '' }}>{{ __('general_content.expired_trans_key') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
                                                <select class="form-control" name="section_id">
                                                    @foreach($sectionsSelect as $section)
                                                        <option value="{{ $section->id }}" {{ $conformite->section_id == $section->id ? 'selected' : '' }}>{{ $section->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                                                <select class="form-control" name="user_id">
                                                    @foreach($userSelect as $user)
                                                        <option value="{{ $user->id }}" {{ $conformite->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
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
                            <th>{{ __('general_content.document_type_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.expiration_date_trans_key') }}</th>
                            <th>{{ __('general_content.status_trans_key') }}</th>
                            <th>{{ __('general_content.section_trans_key') }}</th>
                            <th>{{ __('general_content.user_trans_key') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-adminlte-card>
    </div>
    <div class="col-md-4">
        <form method="POST" action="{{ route('osh.conformities.create') }}">
            @csrf
            <x-adminlte-card title="{{ __('general_content.new_conformity_trans_key') }}" theme="secondary" maximizable>
                <div class="form-group">
                    <label for="document_type">{{ __('general_content.document_type_trans_key') }}</label>
                    <input type="text" class="form-control" name="document_type" placeholder="{{ __('general_content.document_type_trans_key') }}" required>
                </div>
                <div class="form-group">
                    <label for="description">{{ __('general_content.description_trans_key') }}</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="{{ __('general_content.description_trans_key') }}"></textarea>
                </div>
                <div class="form-group">
                    <label for="expiration_date">{{ __('general_content.expiration_date_trans_key') }}</label>
                    <input type="date" class="form-control" name="expiration_date">
                </div>
                <div class="form-group">
                    <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
                    <select class="form-control" name="section_id">
                        @foreach($sectionsSelect as $section)
                            <option value="{{ $section->id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
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