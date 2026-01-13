@extends('adminlte::page')

@section('title', __('general_content.service_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.service_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <x-InfocalloutComponent note="{{ __('general_content.service_info_trans_key') }}"  />
  <div class="row">
    <div class="col-md-8">
      <x-adminlte-card title="{{ __('general_content.service_trans_key') }}" theme="warning" maximizable>
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
                  <x-ButtonTextView route="{{ route('methods.service.show', ['id' => $MethodsService->id])}}" />
                  <!-- Button Modal -->
                  <x-ButtonTextEdit :modalTarget="'MethodsService' . $MethodsService->id" />
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
                            <input type="number" class="form-control" name="hourly_rate" id="hourly_rate" placeholder="110 {{ $Factory->curency }}/H" step=".001" value="{{ $MethodsService->hourly_rate }}">
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
                          @php($selectedSuppliers = $MethodsService->Suppliers->pluck('id')->all())
                          <label for="companies_ids_{{ $MethodsService->id }}">{{ __('general_content.supplier_trans_key') }}</label>
                            <select class="form-control" name="companies_ids[]" id="companies_ids_{{ $MethodsService->id }}" multiple>
                              @forelse ($CompanieSelect as $item)
                              <option value="{{ $item->id }}" @if(in_array($item->id, $selectedSuppliers, true)) Selected @endif>{{ $item->label }}</option>
                              @empty
                              <option value="" disabled>{{ __('general_content.no_select_company_trans_key') }}</option>
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
                <input type="number" class="form-control" name="hourly_rate" id="hourly_rate" placeholder="110 {{ $Factory->curency }}/H" step=".001">
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
              <label for="companies_ids">{{ __('general_content.supplier_trans_key') }}</label>
              <select class="form-control" name="companies_ids[]" id="companies_ids" multiple>
                @forelse ($CompanieSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                @empty
                  <option value="" disabled>{{ __('general_content.no_select_company_trans_key') }}</option>
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
@stop

@section('css')
@stop

@section('js')
@stop
