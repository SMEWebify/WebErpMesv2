@extends('adminlte::page')

@section('title', __('general_content.methods_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.methods_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @include('include.alert-result')

  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Services" data-toggle="tab">{{ __('general_content.service_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Ressources" data-toggle="tab">{{ __('general_content.ressources_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Section" data-toggle="tab">{{ __('general_content.sections_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Location" data-toggle="tab">{{ __('general_content.location_in_workshop_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Units" data-toggle="tab">{{ __('general_content.units_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Families" data-toggle="tab">{{ __('general_content.families_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Tools" data-toggle="tab">{{ __('general_content.tools_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#StandardNomenclature" data-toggle="tab">Standard Nomenclature</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Services">
      <x-InfocalloutComponent note="{{ __('general_content.service_info_trans_key') }}"  />
      <div class="row">
        <div class="col-md-8">
          <x-adminlte-card title="{{ __('general_content.service_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                <tr>
                  <th>{{ __('general_content.picture_trans_key') }}</th>
                  <th>{{ __('general_content.sort_trans_key') }}</th>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th>{{ __('general_content.type_trans_key') }}</th>
                  <th>{{ __('general_content.hourly_rate_trans_key') }}</th>
                  <th>{{ __('general_content.margin_trans_key') }}</th>
                  <th>{{ __('general_content.color_trans_key') }}</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                  @forelse ($MethodsServices as $MethodsService)
                  <tr>
                    <td>
                      @if($MethodsService->picture )
                      <img alt="Service" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'.$MethodsService->picture) }}">
                      @endif
                    </td>
                    <td>{{ $MethodsService->ordre }}</td>
                    <td>{{ $MethodsService->code }}</td>
                    <td>{{ $MethodsService->label }}</td>
                    <td>
                      @if($MethodsService->type  == 1){{ __('general_content.productive_trans_key') }} @endif
                      @if($MethodsService->type  == 2){{ __('general_content.raw_material_trans_key') }} @endif
                      @if($MethodsService->type  == 3){{ __('general_content.raw_material_sheet_trans_key') }} @endif
                      @if($MethodsService->type  == 4){{ __('general_content.raw_material_profil_trans_key') }} @endif
                      @if($MethodsService->type  == 5){{ __('general_content.raw_material_block_trans_key') }} @endif
                      @if($MethodsService->type  == 6){{ __('general_content.supplies_trans_key') }} @endif
                      @if($MethodsService->type  == 7){{ __('general_content.sub_contracting_trans_key') }} @endif
                      @if($MethodsService->type  == 8){{ __('general_content.composed_component_trans_key') }} @endif
                    </td>
                    <td>{{ $MethodsService->hourly_rate }}</td>
                    <td>{{ $MethodsService->margin }} %</td>
                    <td><input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsService->color }}"></td>
                    <td class="py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsService{{ $MethodsService->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $MethodsService->id }} -->
                      <x-adminlte-modal id="MethodsService{{ $MethodsService->id }}" title="Update {{ $MethodsService->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('methods.service.update', ['id' => $MethodsService->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                </div>
                                <input type="number" class="form-control" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" min="0" value="{{ $MethodsService->ordre }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsService->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                                <label for="type">{{ __('general_content.type_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                  </div>
                                  <select class="form-control" name="type" id="type">
                                      <option value="1" @if($MethodsService->type == 1 ) Selected @endif>{{ __('general_content.productive_trans_key') }}</option>
                                      <option value="2" @if($MethodsService->type == 2 ) Selected @endif>{{ __('general_content.raw_material_trans_key') }}</option>
                                      <option value="3" @if($MethodsService->type == 3 ) Selected @endif>{{ __('general_content.raw_material_sheet_trans_key') }}</option>
                                      <option value="4" @if($MethodsService->type == 4 ) Selected @endif>{{ __('general_content.raw_material_profil_trans_key') }}</option>
                                      <option value="5" @if($MethodsService->type == 5 ) Selected @endif>{{ __('general_content.raw_material_block_trans_key') }}</option>
                                      <option value="6" @if($MethodsService->type == 6 ) Selected @endif>{{ __('general_content.supplies_trans_key') }}</option>
                                      <option value="7" @if($MethodsService->type == 7 ) Selected @endif>{{ __('general_content.sub_contracting_trans_key') }}</option>
                                      <option value="8" @if($MethodsService->type == 8 ) Selected @endif>{{ __('general_content.composed_component_trans_key') }}</option>
                                  </select>
                                </div>
                            </div>
                            <div class="form-group">
                              <label for="hourly_rate">{{ __('general_content.hourly_rate_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ $Factory->curency }}/h</span>
                                </div>
                                <input type="number" class="form-control" name="hourly_rate" id="hourly_rate" placeholder="110 €/H" step=".001" value="{{ $MethodsService->hourly_rate }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="margin">{{ __('general_content.margin_trans_key') }} :</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <input type="number" class="form-control" name="margin" id="margin" placeholder="10%" step=".001" value="{{ $MethodsService->hourly_rate }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="color">{{ __('general_content.color_trans_key') }}</label>
                              <input type="color" class="form-control"  name="color" id="color" value="{{ $MethodsService->color }}">
                            </div>
                            <div class="form-group">
                              <label for="compannie_id">{{ __('general_content.supplier_trans_key') }}</label>
                                <select class="form-control" name="compannie_id" id="compannie_id">
                                  <option value="NULL">-</option>
                                  @forelse ($SupplierSelect as $item)
                                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                                  @empty
                                  <option value="NULL">{{ __('general_content.no_select_company_trans_key') }}</option>
                                  @endforelse
                                </select>
                            </div>
                            <!-- /.form-group -->
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                        <div class="card-body">
                          <form action="{{ route('methods.service.update.picture', ['id' => $MethodsService->id]) }}" method="POST" enctype="multipart/form-data">
                              @csrf
                              <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label>(peg,png,jpg,gif,svg | max: 10 240 Ko)
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="far fa-image"></i></span>
                                  </div>
                                  <div class="custom-file">
                                      <input type="hidden" name="id" value="{{ $MethodsService->id }}">
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
                    <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.picture_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.type_trans_key') }}</th>
                    <th>{{ __('general_content.hourly_rate_trans_key') }}</th>
                    <th>{{ __('general_content.margin_trans_key') }}</th>
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
          <form method="POST" action="{{ route('methods.service.create') }}" enctype="multipart/form-data">
            <x-adminlte-card title="{{ __('general_content.new_service_trans_key') }}" theme="secondary" maximizable>
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
                    <label for="type">{{ __('general_content.type_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                      </div>
                      <select class="form-control" name="type" id="type">
                          <option value="1">{{ __('general_content.productive_trans_key') }}</option>
                          <option value="2">{{ __('general_content.raw_material_trans_key') }}</option>
                          <option value="3">{{ __('general_content.raw_material_sheet_trans_key') }}</option>
                          <option value="4">{{ __('general_content.raw_material_profil_trans_key') }}</option>
                          <option value="5">{{ __('general_content.raw_material_block_trans_key') }}</option>
                          <option value="6">{{ __('general_content.supplies_trans_key') }}</option>
                          <option value="7">{{ __('general_content.sub_contracting_trans_key') }}</option>
                          <option value="8">{{ __('general_content.composed_component_trans_key') }}</option>
                      </select>
                    </div>
                </div>
                <div class="form-group">
                  <label for="hourly_rate">{{ __('general_content.hourly_rate_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">{{ $Factory->curency }}/h</span>
                    </div>
                    <input type="number" class="form-control" name="hourly_rate" id="hourly_rate" placeholder="110 €/H" step=".001">
                  </div>
                </div>
                <div class="form-group">
                  <label for="margin">{{ __('general_content.margin_trans_key') }} :</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                    </div>
                    <input type="number" class="form-control" name="margin" id="margin" placeholder="10%" step=".001">
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
                  <label for="compannie_id">{{ __('general_content.supplier_trans_key') }} (no mandatory)</label>
                    <select class="form-control" name="compannie_id" id="compannie_id">
                      <option value="NULL">-</option>
                      @forelse ($SupplierSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @empty
                      <option value="NULL">{{ __('general_content.no_select_company_trans_key') }}</option>
                      @endforelse
                    </select>
                </div>
              <!-- /.form-group -->
              </div>
              <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </div>
            </x-adminlte-card>
          </form>
          <!-- /.card body -->
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Ressources">
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
                                <label for="mask_time">{{__('general_content.mask_time_trans_key') }} ?</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                                  </div>
                                  <select class="form-control" name="mask_time" id="mask_time">
                                      <option value="2" @if($MethodsRessource->mask_time == 2  ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                      <option value="1" @if($MethodsRessource->mask_time == 1  ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                                  </select>
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
                              <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="methods_services_id" id="methods_services_id">
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
                    <label for="mask_time">{{__('general_content.mask_time_trans_key') }} ?</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                      </div>
                      <select class="form-control" name="mask_time" id="mask_time">
                          <option value="2">{{ __('general_content.no_trans_key') }}</option>
                          <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                      </select>
                    </div>
                </div>
                <div class="form-group">
                  <label for="capacity">Hour capacity by week</label>
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
                  <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-list"></i></span>
                    </div>
                    <select class="form-control" name="methods_services_id" id="methods_services_id">
                      @forelse ($ServicesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @empty
                      <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                      @endforelse
                    </select>
                  </div>
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
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Section">
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
                              <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" name="user_id" id="user_id">
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
                <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                  </div>
                  <select class="form-control" name="user_id" id="user_id">
                    @foreach ($userSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
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
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Location">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.location_in_workshop_trans_key') }}" theme="primary" maximizable>
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
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Units">
      <x-InfocalloutComponent note="{{ __('general_content.unit_info_trans_key') }}"  />
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.units_trans_key') }}" theme="primary" maximizable>
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
                              <label for="month_end">{{__('general_content.by_default_trans_key') }}</label>
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
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Families">
      <x-InfocalloutComponent note="{{ __('general_content.family_info_trans_key') }}"  />
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.families_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.service_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($MethodsFamilies as $MethodsFamilie)
                  <tr>
                    <td>{{ $MethodsFamilie->code }}</td>
                    <td>{{ $MethodsFamilie->label }}</td>
                    <td>{{ $MethodsFamilie->service['label'] }}</td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsFamilie{{ $MethodsFamilie->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $MethodsFamilie->id }} -->
                      <x-adminlte-modal id="MethodsFamilie{{ $MethodsFamilie->id }}" title="Update {{ $MethodsFamilie->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('methods.family.update', ['id' => $MethodsFamilie->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsFamilie->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="methods_services_id" id="methods_services_id">
                                  @forelse ($ServicesSelect as $item)
                                  <option value="{{ $item->id }}" @if($MethodsFamilie->methods_services_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                  @empty
                                  <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                                  @endforelse
                                </select>
                              </div>
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
                  <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.service_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
          <!-- /.card secondary -->
        </div>
        <div class="col-md-6">
          <form  method="POST" action="{{ route('methods.family.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_family_trans_key') }}" theme="secondary" maximizable>
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
                <label for="methods_services_id">{{ __('general_content.service_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                  </div>
                  <select class="form-control" name="methods_services_id" id="methods_services_id">
                    @forelse ($ServicesSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @empty
                    <option value="">{{ __('general_content.no_service_trans_key') }}</option>
                    @endforelse
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
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Tools">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.tools_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.picture_trans_key') }}</th>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.etat_trans_key') }}</th>
                    <th>{{ __('general_content.cost_trans_key') }}</th>
                    <th>{{ __('general_content.end_date_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($MethodsTools as $MethodsTool)
                  <tr>
                    <td> 
                      @if($MethodsTool->picture )
                      <img alt="Tool" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'. $MethodsTool->picture) }}">
                      @endif
                    </td>
                    <td>{{ $MethodsTool->code }}</td>
                    <td>{{ $MethodsTool->label }}</td>
                    <td>
                      @if($MethodsTool->ETAT  == 1) {{ __('general_content.unsed_trans_key') }} @endif
                      @if($MethodsTool->ETAT  == 2) {{ __('general_content.used_trans_key') }} @endif
                    </td>
                    <td>{{ $MethodsTool->cost }}</td>
                    <td>{{ $MethodsTool->end_date }}</td>
                    <td>{{ $MethodsTool->qty }}</td>
                    <td class="py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsTool{{ $MethodsTool->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $MethodsTool->id }} -->
                      <x-adminlte-modal id="MethodsTool{{ $MethodsTool->id }}" title="Update {{ $MethodsTool->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('methods.tool.update', ['id' => $MethodsTool->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsTool->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="ETAT">{{ __('general_content.statu_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                </div>
                                <select class="form-control" name="ETAT" id="ETAT">
                                  <option value="1" @if($MethodsTool->ETAT == 1 ) Selected @endif>{{ __('general_content.unsed_trans_key') }}</option>
                                  <option value="2" @if($MethodsTool->ETAT == 2  ) Selected @endif>{{ __('general_content.used_trans_key') }}</option>
                                </select>
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="cost">{{ __('general_content.cost_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ $Factory->curency }}</span>
                                </div>
                                <input type="number" class="form-control" name="cost"  id="cost" placeholder="{{ __('general_content.cost_trans_key') }}" step=".001" value="{{ $MethodsTool->cost }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="qty">{{ __('general_content.qty_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-times"></i></span>
                                </div>
                                <input type="numer" class="form-control" name="qty"  id="qty" placeholder="{{__('general_content.qty_trans_key') }}" value="{{ $MethodsTool->qty }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                              <input type="date" class="form-control" name="end_date"  id="end_date" placeholder="{{__('general_content.qty_trans_key') }}" value="{{ $MethodsTool->end_date }}" >
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                        <div class="card-body">
                          <form action="{{ route('methods.tool.update.picture', ['id' => $MethodsTool->id]) }}" method="POST" enctype="multipart/form-data">
                              @csrf
                              <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label>(peg,png,jpg,gif,svg | max: 10 240 Ko)
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="far fa-image"></i></span>
                                  </div>
                                  <div class="custom-file">
                                      <input type="hidden" name="id" value="{{ $MethodsTool->id }}">
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
                  <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.picture_trans_key') }}</th>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.etat_trans_key') }}</th>
                    <th>{{ __('general_content.cost_trans_key') }}</th>
                    <th>{{ __('general_content.end_date_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        </div>
        <div class="col-md-6">
          <form  method="POST" action="{{ route('methods.tool.create') }}" class="form-horizontal" enctype="multipart/form-data">
            <x-adminlte-card title="{{ __('general_content.new_tool_trans_key') }}" theme="secondary" maximizable>
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
                <label for="ETAT">{{ __('general_content.statu_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="ETAT" id="ETAT">
                    <option value="1">{{ __('general_content.unsed_trans_key') }}</option>
                    <option value="2">{{ __('general_content.used_trans_key') }}</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="cost">{{ __('general_content.cost_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text">{{ $Factory->curency }}</span>
                  </div>
                  <input type="number" class="form-control" name="cost"  id="cost" placeholder="{{ __('general_content.cost_trans_key') }}" step=".001">
                </div>
              </div>
              <div class="form-group">
                <label for="qty">{{ __('general_content.qty_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-times"></i></span>
                  </div>
                  <input type="numer" class="form-control" name="qty"  id="qty" placeholder="{{__('general_content.qty_trans_key') }}" >
                </div>
              </div>
              <div class="form-group">
                <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                <input type="date" class="form-control" name="end_date"  id="end_date">
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
    <!-- /.tab-pane -->
    
  </div>
  <div class="tab-pane" id="StandardNomenclature">
    <div class="row">
      <div class="col-md-6">
        <x-adminlte-card title="{{ __('general_content.standard_bom_trans_key') }}" theme="primary" maximizable>
          <div class="table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th></th>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($MethodsStandardNomenclatures as $MethodsStandardNomenclature)
                <tr>
                  <td>#{{ $MethodsStandardNomenclature->id }}</td>
                  <td>{{ $MethodsStandardNomenclature->code }}</td>
                  <td>{{ $MethodsStandardNomenclature->label }}</td>
                  <td>{{ $MethodsStandardNomenclature->getAllTaskCountAttribute() }}</td>
                  <td class="py-0 align-middle">
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('task.manage', ['id_type'=> 'nomenclature_lines_id', 'id_page'=>  $MethodsStandardNomenclature->id, 'id_line' => $MethodsStandardNomenclature->id])}}" class="btn bg-primary"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                    </div>                    
                    <!-- Button Modal -->
                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#MethodsStandardNomenclature{{ $MethodsStandardNomenclature->id }}">
                      <i class="fa fa-lg fa-fw  fa-edit"></i>
                    </button>
                    <!-- Modal {{ $MethodsStandardNomenclature->id }} -->
                    <x-adminlte-modal id="MethodsStandardNomenclature{{ $MethodsStandardNomenclature->id }}" title="Update {{ $MethodsStandardNomenclature->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                      <form method="POST" action="{{ route('methods.standard.nomenclature.update', ['id' => $MethodsStandardNomenclature->id]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                          <div class="form-group">
                            <label for="label">{{__('general_content.label_trans_key') }}</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $MethodsStandardNomenclature->label }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <x-FormTextareaComment  comment="{{ $MethodsStandardNomenclature->comment }}" />
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
                <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th></th>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </x-adminlte-card>
      </div>
      <div class="col-md-6">
        <form  method="POST" action="{{ route('methods.standard.nomenclature.create') }}" class="form-horizontal" enctype="multipart/form-data">
          <x-adminlte-card title="{{ __('general_content.new_standard_bom_trans_key') }}" theme="secondary" maximizable>
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
              <x-FormTextareaComment  comment="" />
            </div>
            <div class="card-footer">
              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          </x-adminlte-card>
        </form>
      </div>
    </div>
    <!-- /.row -->
  </div>
  <!-- /.tab-pane -->

  <!-- /.tab-content -->
</div>
@stop

@section('css')
@stop

@section('js')
@stop