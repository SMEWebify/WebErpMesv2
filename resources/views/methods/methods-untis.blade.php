@extends('adminlte::page')

@section('title', __('general_content.units_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.units_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

  @include('include.alert-result')
  <div class="tab-pane" id="Units">
    <x-InfocalloutComponent note="{{ __('general_content.unit_info_trans_key') }}"  />
    <div class="row">
      <div class="col-md-6">
        <x-adminlte-card title="{{ __('general_content.units_trans_key') }}" theme="teal" maximizable>
          <div class="table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th>{{ __('general_content.type_trans_key') }}</th>
                  <th></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($MethodsUnits as $MethodsUnit)
                <tr>
                  <td>{{ $MethodsUnit->code }}</td>
                  <td>{{ $MethodsUnit->label }}</td>
                  <td>
                    @if($MethodsUnit->type  == 1) {{ __('general_content.mass_trans_key') }} @endif
                    @if($MethodsUnit->type  == 2) {{ __('general_content.length_trans_key') }} @endif
                    @if($MethodsUnit->type  == 3) {{ __('general_content.aera_trans_key') }} @endif
                    @if($MethodsUnit->type  == 4) {{ __('general_content.volume_trans_key') }} @endif
                    @if($MethodsUnit->type  == 5) {{ __('general_content.other_trans_key') }} @endif
                  </td>
                  <td>
                    <div class="custom-control custom-radio">
                      <input class="custom-control-input" type="radio" id="customRadio{{ $MethodsUnit->id }}" name="customRadio"  @if( $MethodsUnit->default == 1 ) checked @endif disabled>
                      <label for="customRadio{{ $MethodsUnit->id }}" class="custom-control-label">by default</label>
                    </div>
                  </td>
                  <td class=" py-0 align-middle">
                    <!-- Button Modal -->
                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsUnit{{ $MethodsUnit->id }}">
                      <i class="fa fa-lg fa-fw  fa-edit"></i>
                    </button>
                    <!-- Modal {{ $MethodsUnit->id }} -->
                    <x-adminlte-modal id="MethodsUnit{{ $MethodsUnit->id }}" title="Update {{ $MethodsUnit->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                      <form method="POST" action="{{ route('methods.unit.update', ['id' => $MethodsUnit->id]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                          <div class="form-group">
                            <label for="label">{{__('general_content.label_trans_key') }}</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsUnit->label }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="type">{{ __('general_content.type_trans_key') }}</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                              </div>
                              <select class="form-control" name="type" id="type">
                                  <option value="1" @if($MethodsUnit->type == 1  ) Selected @endif>{{ __('general_content.mass_trans_key') }}</option>
                                  <option value="2" @if($MethodsUnit->type == 2  ) Selected @endif>{{ __('general_content.length_trans_key') }}</option>
                                  <option value="3" @if($MethodsUnit->type == 3  ) Selected @endif>{{ __('general_content.aera_trans_key') }}</option>
                                  <option value="4" @if($MethodsUnit->type == 4  ) Selected @endif>{{ __('general_content.volume_trans_key') }}</option>
                                  <option value="5" @if($MethodsUnit->type == 5  ) Selected @endif>{{ __('general_content.other_trans_key') }}</option>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="default">{{__('general_content.by_default_trans_key') }}</label>
                            <select class="form-control" name="default" id="default">
                                <option value="0" @if($MethodsUnit->default == 0) selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                <option value="1" @if($MethodsUnit->default == 1) selected @endif>{{ __('general_content.yes_trans_key') }}</option>
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
                <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th>{{ __('general_content.type_trans_key') }}</th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </x-adminlte-card>
      <!-- /.card secondary -->
      </div>
      <div class="col-md-6">
        <form  method="POST" action="{{ route('methods.unit.create') }}" class="form-horizontal">
          <x-adminlte-card title="{{ __('general_content.new_unit_trans_key') }}" theme="secondary" maximizable>
            @csrf
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
                <label for="type">{{ __('general_content.type_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                  </div>
                  <select class="form-control" name="type" id="type">
                      <option value="1">{{ __('general_content.mass_trans_key') }}</option>
                      <option value="2">{{ __('general_content.length_trans_key') }}</option>
                      <option value="3">{{ __('general_content.aera_trans_key') }}</option>
                      <option value="4">{{ __('general_content.volume_trans_key') }}</option>
                      <option value="5">{{ __('general_content.other_trans_key') }}</option>
                  </select>
                </div>
            </div>
            <div class="card-footer">
              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          </x-adminlte-card>
        </form>
      </div>
      <!-- /.card secondary -->
    </div>
    <!-- /.row -->
  </div>
@stop

@section('css')
@stop

@section('js')
@stop