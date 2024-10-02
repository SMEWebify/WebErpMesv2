@extends('adminlte::page')

@section('title', __('general_content.location_in_workshop_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.location_in_workshop_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.location_in_workshop_trans_key') }}" theme="danger" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.ressource_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($MethodsLocations as $MethodsLocation)
              <tr>
                <td>{{ $MethodsLocation->code }}</td>
                <td>{{ $MethodsLocation->label }}</td>
                <td>{{ $MethodsLocation->ressources['label'] }}</td>
                <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsLocation->color }}"></td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsLocation{{ $MethodsLocation->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $MethodsLocation->id }} -->
                  <x-adminlte-modal id="MethodsLocation{{ $MethodsLocation->id }}" title="Update {{ $MethodsLocation->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.location.update', ['id' => $MethodsLocation->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsLocation->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="color">{{ __('general_content.color_trans_key') }}</label>
                          <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsLocation->color }}">
                        </div>
                        <div class="form-group">
                          <label for="ressource_id">{{ __('general_content.ressource_trans_key') }}</label>
                          <select class="form-control" name="ressource_id" id="ressource_id">
                            @foreach ($RessourcesSelect as $item)
                            <option value="{{ $item->id }}" @if($MethodsLocation->ressource_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                            @endforeach
                          </select>
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
              <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{ __('general_content.ressource_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </x-adminlte-card>
      <!-- /.card secondary -->
    </div>
    <div class="col-md-6">
      <form  method="POST" action="{{ route('methods.location.create') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('general_content.new_location_trans_key') }}" theme="secondary" maximizable>
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
            <label for="color">{{ __('general_content.color_trans_key') }}</label>
            <input type="color" class="form-control"  name="color" id="color" >
          </div>
          <div class="form-group">
            <label for="ressource_id">{{ __('general_content.ressource_trans_key') }}</label>
            <select class="form-control" name="ressource_id" id="ressource_id">
              @foreach ($RessourcesSelect as $item)
              <option value="{{ $item->id }}">{{ $item->label }}</option>
              @endforeach
            </select>
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
@stop

@section('css')
@stop

@section('js')
@stop