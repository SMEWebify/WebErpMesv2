@extends('adminlte::page')

@section('title', __('general_content.action_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.action_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

      <x-InfocalloutComponent note="{{ __('general_content.action_note_trans_key') }}"  />
      @include('include.alert-result')
      <x-adminlte-card theme="primary" theme-mode="outline">
        <div class="table-responsive p-0">
          <table class="table  table-striped table-hover">
            <thead>
            <tr>
              <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.type_trans_key') }}</th>
                <th>{{__('general_content.status_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th>{{ __('general_content.nc_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
              @forelse ($QualityActions as $QualityAction)
              <tr>
                <td>{{ $QualityAction->code }}</td>
                <td>{{ $QualityAction->label }}</td>
                <td><img src="{{ Avatar::create($QualityAction->UserManagement['name'])->toBase64() }}" /></td>
                <td>
                  @if($QualityAction->type  == 1) <span class="badge badge-warning">{{ __('general_content.internal_trans_key') }}</span> @endif
                  @if($QualityAction->type  == 2) <span class="badge badge-danger">{{ __('general_content.external_trans_key') }}</span> @endif
                </td>
                <td>
                  @if($QualityAction->statu  == 1) <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                  @if($QualityAction->statu  == 2) <span class="badge badge-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @endif
                  @if($QualityAction->statu  == 3) <span class="badge badge-success">{{ __('general_content.validate_trans_key') }}</span> @endif
                  @if($QualityAction->statu  == 4) <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span> @endif
                </td>
                <td><input type="color" class="form-control"  name="color" id="color" value="{{ $QualityAction->color }}"></td>
                <td>
                  @if($QualityAction->quality_non_conformitie_id) 
                    {{ $QualityAction->QualityNonConformity->code }} 
                  @else
                    {{ __('general_content.no_nc_trans_key') }} 
                  @endif
                </td>
                <td>{{ $QualityAction->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityActionView{{ $QualityAction->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityAction->id }} -->
                  <x-adminlte-modal id="QualityActionView{{ $QualityAction->id }}" title="Info {{ $QualityAction->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    <div class="row">
                      <strong>{{ __('general_content.problem_description_trans_key') }} : </strong> 
                      {{ $QualityAction->pb_descp }}
                    </div>
                    <div class="row">
                      <strong >{{ __('general_content.cause_description_trans_key') }} :</strong> 
                      {{ $QualityAction->cause }}
                    </div>
                    <div class="row">
                      <strong >{{ __('general_content.action_description_trans_key') }} :</strong> 
                      {{ $QualityAction->action }}
                    </div>
                  </x-adminlte-modal>

                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityAction{{ $QualityAction->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $QualityAction->id }} -->
                  <x-adminlte-modal id="QualityAction{{ $QualityAction->id }}" title="Update {{ $QualityAction->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('quality.action.update', ['id' => $QualityAction->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityAction->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="statu" id="statu">
                              <option value="1"  @if($QualityAction->statu == 1  ) Selected @endif>{{ __('general_content.in_progress_trans_key') }}</option>
                              <option value="2"  @if($QualityAction->statu == 2  ) Selected @endif>{{ __('general_content.waiting_customer_data_trans_key') }}</option>
                              <option value="3"  @if($QualityAction->statu == 3  ) Selected @endif>{{ __('general_content.validate_trans_key') }}</option>
                              <option value="4"  @if($QualityAction->statu == 4  ) Selected @endif>{{ __('general_content.canceled_trans_key') }}</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="type">{{ __('general_content.type_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="type" id="type">
                              <option value="1" @if($QualityAction->type == 1  ) Selected @endif>{{ __('general_content.internal_trans_key') }}</option>
                              <option value="2" @if($QualityAction->type == 2  ) Selected @endif>{{ __('general_content.external_trans_key') }}</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <select class="form-control" name="user_id" id="user_id">
                              @foreach ($userSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityAction->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="color">{{ __('general_content.color_trans_key') }}</label>
                          <input type="color" class="form-control"  name="color" id="color" value="{{ $QualityAction->color }}">
                        </div>
                        <div class="form-group">
                          <label for="quality_non_conformitie_id">{{ __('general_content.non_conformitie_trans_key') }}</label>
                          <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                            <option value="null">-</option>
                            @foreach ($NonConformitysSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityAction->quality_non_conformitie_id == $item->id  ) Selected @endif>{{ $item->code }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.problem_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="pb_descp"  placeholder="..." required>{{ $QualityAction->pb_descp }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.cause_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="cause"  placeholder="..." required>{{ $QualityAction->cause }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.action_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="action"  placeholder="..." required>{{ $QualityAction->action }}</textarea>
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
              <x-EmptyDataLine col="8" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.type_trans_key') }}</th>
                <th>{{__('general_content.status_trans_key') }}</th>
                <th>{{ __('general_content.color_trans_key') }}</th>
                <th>{{ __('general_content.nc_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="form-group col-md-5">
          {{ $QualityActions->links() }}
          </div>
        <!-- /.row -->
        </div>
      </x-adminlte-card>

      <form method="POST" action="{{ route('quality.action.create')}}" >
        <x-adminlte-card title="{{ __('general_content.new_action_trans_key') }}" theme="secondary" maximizable>
          @csrf
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="ACT-{{ $LastAction->id ?? '0' }}">
              </div>
            </div>
            <div class="form-group col-md-4">
              <label for="label">{{__('general_content.label_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
              </div>
            </div>
            <div class="form-group col-md-4">
              <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                </div>
                <select class="form-control" name="statu" id="statu">
                  <option value="1">{{ __('general_content.in_progress_trans_key') }}</option>
                  <option value="2">{{ __('general_content.waiting_customer_data_trans_key') }}</option>
                  <option value="3">{{ __('general_content.validate_trans_key') }}</option>
                  <option value="4">{{ __('general_content.canceled_trans_key') }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label for="type">{{ __('general_content.type_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                </div>
                <select class="form-control" name="type" id="type">
                  <option value="1">{{ __('general_content.internal_trans_key') }}</option>
                  <option value="2">{{ __('general_content.external_trans_key') }}</option>
                </select>
              </div>
            </div>
            <div class="form-group col-md-3">
              <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
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
            <div class="form-group col-md-3">
              <label for="color">{{ __('general_content.color_trans_key') }}</label>
              <input type="color" class="form-control"  name="color" id="color" >
            </div>
            <div class="form-group col-md-3">
              <label for="quality_non_conformitie_id">{{ __('general_content.non_conformitie_trans_key') }}</label>
              <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                <option value="null">-</option>
                @foreach ($NonConformitysSelect as $item)
                <option value="{{ $item->id }}">{{ $item->code }}</option>
                @endforeach
              </select>
            </div>
            <!-- /.row -->
          </div>
          <div class="row">
            <div class="form-group col-md-3">
              <label>{{ __('general_content.problem_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="pb_descp"  placeholder="..."></textarea>
            </div>
            <div class="form-group col-md-3">
              <label>{{ __('general_content.cause_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="cause"  placeholder="..."></textarea>
            </div>
            <div class="form-group col-md-3">
              <label>{{ __('general_content.action_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="action"  placeholder="..."></textarea>
            </div>
            <!-- /.row -->
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        <!-- /.card-body -->
        </x-adminlte-card>
      </form>
@stop

@section('css')
@stop

@section('js')
@stop