@extends('adminlte::page')

@section('title', __('general_content.ressources_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.ressources_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent note="{{ __('general_content.ressource_info_trans_key') }}"  />
  <div class="row">
    <div class="col-md-8">
      <x-adminlte-card title="{{ __('general_content.ressources_trans_key') }}" theme="primary" maximizable>
        <div class="table-responsive p-0">
          <table class="table table-hover">
            <thead>
            <tr>
              <th>{{ __('general_content.picture_trans_key') }}</th>
              <th>{{ __('general_content.sort_trans_key') }}</th>
              <th>{{ __('general_content.external_id_trans_key') }}</th>
              <th>{{ __('general_content.description_trans_key') }}</th>
              <th>{{__('general_content.mask_time_trans_key') }}</th>
              <th>{{__('general_content.capacity_trans_key') }}</th>
              <th>{{ __('general_content.section_trans_key') }}</th>
              <th>{{ __('general_content.color_trans_key') }}</th>
              <th>{{ __('general_content.service_trans_key') }}</th>
              <th></th>
            </tr>
            </thead>
            <tbody>
              @forelse ($MethodsRessources as $MethodsRessource)
              <tr>
                <td>
                  @if($MethodsRessource->picture )
                  <img alt="Ressource" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/ressources/'.$MethodsRessource->picture) }}">
                  @endif
                </td>
                <td>{{ $MethodsRessource->ordre }}</td>
                <td>{{ $MethodsRessource->code }}</td>
                <td>{{ $MethodsRessource->label }}</td>
                <td>
                  @if($MethodsRessource->mask_time == 1  ) {{ __('general_content.yes_trans_key') }} @endif
                  @if($MethodsRessource->mask_time == 2  ) {{ __('general_content.no_trans_key') }} @endif
                </td>
                <td>{{ $MethodsRessource->capacity }} h/w</td>
                <td>{{ $MethodsRessource->section['label'] }}</td>
                <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsRessource->color }}"></td>
                <td>{{ $MethodsRessource->service['label'] }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsRessource{{ $MethodsRessource->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $MethodsRessource->id }} -->
                  <x-adminlte-modal id="MethodsRessource{{ $MethodsRessource->id }}" title="Update {{ $MethodsRessource->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('methods.ressource.update', ['id' => $MethodsRessource->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            </div>
                            <input type="number" class="form-control" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" min="0" value="{{ $MethodsRessource->ordre }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsRessource->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <div class="col-4 text-left"><label for="mask_time_update{{ $MethodsRessource->id }}" class="col-form-label">{{ __('general_content.mask_time_trans_key') }}</label></div>
                          <div class="col-8">
                              @if($MethodsRessource->mask_time == 1)  
                              <x-adminlte-input-switch id="mask_time_update{{ $MethodsRessource->id }}" name="mask_time_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                              @else
                              <x-adminlte-input-switch id="mask_time_update{{ $MethodsRessource->id }}" name="mask_time_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                              @endif
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="capacity">Hour capacity by week</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                            </div>
                            <input type="number" class="form-control" name="capacity" id="capacity" placeholder="110 h/week" value="{{ $MethodsRessource->capacity }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="color">{{ __('general_content.color_trans_key') }}</label>
                          <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsRessource->color }}">
                        </div>
                        <div class="form-group">
                          <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-industry"></i></span>
                            </div>
                            <select class="form-control" name="section_id" id="section_id">
                              @forelse ($SectionsSelect as $item)
                              <option value="{{ $item->id }}" @if($MethodsRessource->section_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @empty
                              <option value="">{{ __('general_content.no_section_trans_key') }}</option>
                              @endforelse
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="methods_services_id_{{ $MethodsRessource->id }}">{{ __('general_content.service_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="methods_services_id" id="methods_services_id_{{ $MethodsRessource->id }}">
                              @forelse ($ServicesSelect as $item)
                              <option value="{{ $item->id }}" @if($MethodsRessource->methods_services_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @empty
                              <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                              @endforelse
                            </select>
                          </div>
                        </div>
                        <!-- /.form-group -->
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                    <div class="card-body">
                      <form action="{{ route('methods.ressource.update.picture', ['id' => $MethodsRessource->id]) }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label>(peg,png,jpg,gif,svg | max: 10 240 Ko)
                          <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="far fa-image"></i></span>
                              </div>
                              <div class="custom-file">
                                  <input type="hidden" name="id" value="{{ $MethodsRessource->id }}">
                                  <input type="file" class="custom-file-input" name="picture" id="picture">
                                  <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                              </div>
                              <div class="input-group-append">
                                  <button type="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
                              </div>
                          </div>
                      </form>
                    </div>
                  </x-adminlte-modal>
                </td>
              </tr>
              @empty
                <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{ __('general_content.picture_trans_key') }}</th>
                <th>{{ __('general_content.sort_trans_key') }}</th>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{ __('general_content.description_trans_key') }}</th>
                <th>{{__('general_content.mask_time_trans_key') }}</th>
                <th>{{__('general_content.capacity_trans_key') }}</th>
                <th>{{ __('general_content.section_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th>{{ __('general_content.service_trans_key') }}</th>
                <th></th>
              </tr>
            </tfoot>
            </table>
        </div>
      </x-adminlte-card>
    <!-- /.card secondary -->
    </div>
    <div class="col-md-4">
      <form method="POST" action="{{ route('methods.ressource.create')}}" enctype="multipart/form-data">
        <x-adminlte-card title="{{ __('general_content.new_ressource_trans_key') }}" theme="secondary" maximizable>
          @csrf
            <div class="form-group">
              <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
              </div>
            </div>
            <div class="form-group">
              <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                </div>
                <input type="number" class="form-control" name="ordre" id="ordre" min="0" placeholder="{{ __('general_content.sort_trans_key') }}">
              </div>
            </div>
            <div class="form-group">
              <label for="label">{{__('general_content.label_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
              </div>
            </div>
            <div class="form-group">
                <label for="mask_time" class="col-form-label">{{ __('general_content.mask_time_trans_key') }}</label>
                <x-adminlte-input-switch name="mask_time" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}"
                data-on-color="teal" checked/>
            </div>
            <div class="form-group">
              <label for="capacity">{{__('general_content.hour_capacity_week_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                </div>
                <input type="number" class="form-control" name="capacity" id="capacity" placeholder="110 h/week">
              </div>
            </div>
            <div class="form-group">
              <label for="color">{{ __('general_content.color_trans_key') }}</label>
              <input type="color" class="form-control"  name="color" id="color" >
            </div>
            <div class="form-group">
              
              <label for="picture">{{ __('general_content.picture_trans_key') }}</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="far fa-image"></i></span>
                </div>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="picture" id="picture">
                    <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                </div>
            </div>
            <div class="form-group">
              <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-industry"></i></span>
                </div>
                <select class="form-control" name="section_id" id="section_id">
                  @forelse ($SectionsSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @empty
                  <option value="">{{ __('general_content.no_section_trans_key') }}</option>
                  @endforelse
                </select>
              </div>
            </div>
            <div class="form-group">
              @include('include.form.form-select-service',['serviceId' =>  null ])
            </div>
          <!-- /.form-group -->
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