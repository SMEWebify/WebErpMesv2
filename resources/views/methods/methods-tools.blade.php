@extends('adminlte::page')

@section('title', __('general_content.tools_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.tools_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-6">
      <x-adminlte-card title="{{ __('general_content.tools_trans_key') }}" theme="gray" maximizable>
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
                  @if($MethodsTool->ETAT  == 1) {{ __('general_content.used_trans_key') }} @endif
                  @if($MethodsTool->ETAT  == 2) {{ __('general_content.unsed_trans_key') }} @endif
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
                          <div class="col-4 text-left"><label for="etat_update{{ $MethodsTool->id }}" class="col-form-label">{{ __('general_content.etat_trans_key') }}</label></div>
                          <div class="col-8">
                              @if($MethodsTool->ETAT == 1)  
                              <x-adminlte-input-switch id="etat_update{{ $MethodsTool->id }}" name="etat_update" data-on-text="{{ __('general_content.used_trans_key') }}" data-off-text="{{ __('general_content.unsed_trans_key') }}" data-on-color="teal" checked/>
                              @else
                              <x-adminlte-input-switch id="etat_update{{ $MethodsTool->id }}" name="etat_update" data-on-text="{{ __('general_content.used_trans_key') }}" data-off-text="{{ __('general_content.unsed_trans_key') }}" data-on-color="teal" />
                              @endif
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
              <label for="ETAT" class="col-form-label">{{ __('general_content.etat_trans_key') }}</label>
              <x-adminlte-input-switch name="ETAT" data-on-text="{{ __('general_content.used_trans_key') }}" data-off-text="{{ __('general_content.unsed_trans_key') }}"
              data-on-color="teal" checked/>
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
@stop

@section('css')
@stop

@section('js')
@stop