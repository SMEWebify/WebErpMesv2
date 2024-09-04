@extends('adminlte::page')

@section('title', __('general_content.sections_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.sections_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent note="{{ __('general_content.section_info_trans_key') }}"  />
  <div class="row">
    <div class="col-md-8">
      <x-adminlte-card title="{{ __('general_content.sections_trans_key') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('general_content.sort_trans_key') }}</th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($MethodsSections as $MethodsSection)
              <tr>
                <td>{{ $MethodsSection->ordre }}</td>
                <td>{{ $MethodsSection->code }}</td>
                <td>{{ $MethodsSection->label }}</td>
                <td><img src="{{ Avatar::create($MethodsSection->UserManagement['name'])->toBase64() }}" /></td>
                <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsSection->color }}"></td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsSection{{ $MethodsSection->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $MethodsSection->id }} -->
                  <x-adminlte-modal id="MethodsSection{{ $MethodsSection->id }}" title="Update {{ $MethodsSection->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.section.update', ['id' => $MethodsSection->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            </div>
                            <input type="number" class="form-control" name="ordre" id="ordre" placeholder="10" min="0" value="{{ $MethodsSection->ordre }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsSection->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="color">{{ __('general_content.color_trans_key') }}</label>
                          <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsSection->color }}">
                        </div>
                        
                        <div class="form-group">
                          <label for="user_id_{{ $MethodsSection->id }}">{{ __('general_content.user_management_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" name="user_id" id="user_id_{{ $MethodsSection->id }}">
                              @foreach ($userSelect as $item)
                              <option value="{{ $item->id }}" @if($MethodsSection->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <!-- /.form-group -->
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
              <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{ __('general_content.sort_trans_key') }}</th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </x-adminlte-card>
    <!-- /.card secondary -->
    </div>
    <div class="col-md-4">
      <form  method="POST" action="{{ route('methods.section.create') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('general_content.new_section_trans_key') }}" theme="secondary" maximizable>
          @csrf
          <div class="form-group">
            <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
              </div>
              <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="10">
            </div>
          </div>
          <div class="form-group">
            <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
              </div>
              <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
            </div>
          </div>
          <div class="form-group">
            <label for="label">{{__('general_content.label_trans_key') }}</label>
            <div class="input-group">
              <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
              </div>
              <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
            </div>
          </div>
          <div class="form-group">
            <label for="color">{{ __('general_content.color_trans_key') }}</label>
            <input type="color" class="form-control"  name="color" id="color" >
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
    <!-- /.card secondary -->
  </div>
@stop

@section('css')
@stop

@section('js')
@stop