@extends('adminlte::page')

@section('title', __('general_content.quality_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.quality_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Actions" data-toggle="tab">{{ __('general_content.action_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#Derogations" data-toggle="tab">{{ __('general_content.derogations_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#NonConformities" data-toggle="tab">{{ __('general_content.non_conformities_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#MeasuringDevices" data-toggle="tab">{{ __('general_content.measuring_devices_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Settings" data-toggle="tab">{{ __('general_content.settings_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Actions">
      <div class="card-body">
        <x-InfocalloutComponent note="{{ __('general_content.action_note_trans_key') }}"  />
        @include('include.alert-result')
        <div class="row">
          <table  class="table  table-striped">
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
                <td>{{ $QualityAction->UserManagement['name'] }}</td>
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
          <div class="col-5">
          {{ $QualityActions->links() }}
          </div>
        <!-- /.row -->
        </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card card-secondary">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.new_action_trans_key') }}</h3>
        </div>
        <form method="POST" action="{{ route('quality.action.create')}}" >
          <div class="card-body">
            @csrf
            <div class="form-group row">
              <div class="col-4">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="ACT-{{ $LastAction->id ?? '0' }}">
                </div>
              </div>
              <div class="col-4">
                <label for="label">{{__('general_content.label_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
                </div>
              </div>
              <div class="col-4">
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
            <div class="form-group row">
              <div class="col-3">
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
              <div class="col-3">
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
              <div class="col-3">
                <label for="color">{{ __('general_content.color_trans_key') }}</label>
                <input type="color" class="form-control"  name="color" id="color" >
              </div>
              <div class="col-3">
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
              <div class="col-3">
                <label>{{ __('general_content.problem_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="pb_descp"  placeholder="..."></textarea>
              </div>
              <div class="col-3">
                <label>{{ __('general_content.cause_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="cause"  placeholder="..."></textarea>
              </div>
              <div class="col-3">
                <label>{{ __('general_content.action_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="action"  placeholder="..."></textarea>
              </div>
              
            <!-- /.row -->
            </div>
          </div>
          <div class="card-footer">
              <div class="form-group row">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </div>
          </div>
        </form>
      <!-- /.card-body -->
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Derogations">
      @include('include.alert-result')
      <div class="card-body">
        <div class="row">
          <table  class="table  table-striped">
            <thead>
              <tr>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.type_trans_key') }}</th>
                <th>{{__('general_content.status_trans_key') }}</th>
                <th>{{ __('general_content.nc_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($QualityDerogations as $QualityDerogation)
              <tr>
                <td>{{ $QualityDerogation->code }}</td>
                <td>{{ $QualityDerogation->label }}</td>
                <td>{{ $QualityDerogation->UserManagement['name'] }}</td>
                <td>
                  @if($QualityDerogation->type  == 1) <span class="badge badge-warning">{{ __('general_content.internal_trans_key') }}</span> @endif
                  @if($QualityDerogation->type  == 2) <span class="badge badge-danger">{{ __('general_content.external_trans_key') }}</span> @endif
                </td>
                <td>
                  @if($QualityDerogation->statu  == 1) <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                  @if($QualityDerogation->statu  == 2) <span class="badge badge-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @endif
                  @if($QualityDerogation->statu  == 3) <span class="badge badge-success">{{ __('general_content.validate_trans_key') }}</span> @endif
                  @if($QualityDerogation->statu  == 4) <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span> @endif
                </td>
                <td>
                  @if($QualityDerogation->quality_non_conformitie_id) 
                    {{ $QualityDerogation->QualityNonConformity->code }} 
                  @else
                    {{ __('general_content.no_nc_trans_key') }}
                  @endif
                </td>
                <td>{{ $QualityDerogation->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityDerogationView{{ $QualityDerogation->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityDerogation->id }} -->
                  <x-adminlte-modal id="QualityDerogationView{{ $QualityDerogation->id }}" title="Info {{ $QualityDerogation->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    <div class="row">
                      <strong>{{ __('general_content.problem_description_trans_key') }} : </strong> 
                      {{ $QualityDerogation->pb_descp }}
                    </div>
                    <div class="row">
                      <strong >{{ __('general_content.proposal_description_trans_key') }} :</strong> 
                      {{ $QualityDerogation->proposal }}
                    </div>
                    <div class="row">
                      <strong >{{ __('general_content.customer_reply_description_trans_key') }} :</strong> 
                      {{ $QualityDerogation->reply }}
                    </div>
                    <div class="row">
                      <strong >{{ __('general_content.decision_description_trans_key') }} :</strong> 
                      {{ $QualityDerogation->decision }}
                    </div>
                  </x-adminlte-modal>

                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityDerogation{{ $QualityDerogation->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $QualityDerogation->id }} -->
                  <x-adminlte-modal id="QualityDerogation{{ $QualityDerogation->id }}" title="Update {{ $QualityDerogation->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('quality.derogation.update', ['id' => $QualityDerogation->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityDerogation->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="statu" id="statu">
                              <option value="1"  @if($QualityDerogation->statu == 1  ) Selected @endif>{{ __('general_content.in_progress_trans_key') }}</option>
                              <option value="2"  @if($QualityDerogation->statu == 2  ) Selected @endif>{{ __('general_content.waiting_customer_data_trans_key') }}</option>
                              <option value="3"  @if($QualityDerogation->statu == 3  ) Selected @endif>{{ __('general_content.validate_trans_key') }}</option>
                              <option value="4"  @if($QualityDerogation->statu == 4  ) Selected @endif>{{ __('general_content.canceled_trans_key') }}</option>
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
                              <option value="1" @if($QualityDerogation->type == 1  ) Selected @endif>{{ __('general_content.internal_trans_key') }}</option>
                              <option value="2" @if($QualityDerogation->type == 2  ) Selected @endif>{{ __('general_content.external_trans_key') }}</option>
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
                              <option value="{{ $item->id }}"  @if($QualityDerogation->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="quality_non_conformitie_id">{{ __('general_content.non_conformitie_trans_key') }}</label>
                          <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                            <option value="null">-</option>
                            @foreach ($NonConformitysSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityDerogation->quality_non_conformitie_id == $item->id  ) Selected @endif>{{ $item->code }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.problem_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="pb_descp"  placeholder="..." required>{{ $QualityDerogation->pb_descp }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.proposal_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="proposal"  placeholder="..." required>{{ $QualityDerogation->proposal }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.customer_reply_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="reply"  placeholder="..." required>{{ $QualityDerogation->reply }}</textarea>
                        </div>
                        <div class="form-group">
                          <label>{{ __('general_content.decision_description_trans_key') }}</label>
                          <textarea class="form-control" rows="3" name="decision"  placeholder="..." required>{{ $QualityDerogation->decision }}</textarea>
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
                <th>{{ __('general_content.nc_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="col-5">
          {{ $QualityDerogations->links() }}
          </div>
        <!-- /.row -->
        </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card card-secondary">
        <div class="card-header">
          <h3 class="card-title">{{__('general_content.new_derogation_trans_key') }}</h3>
        </div>
        <form method="POST" action="{{ route('quality.derogation.create')}}" >  
          <div class="card-body">
            @csrf
            <div class="form-group row">
              <div class="col-2">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="DER-{{ $LastDerogation->id ?? '0' }}  ">
                </div>
              </div>
              <div class="col-2">
                <label for="label">{{__('general_content.label_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
                </div>
              </div>
              <div class="col-2">
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
              <div class="col-2">
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
              <div class="col-2">
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
              <div class="col-2">
                <label for="quality_non_conformitie_id">{{ __('general_content.non_conformitie_trans_key') }}</label>
                <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                  <option value="null">-</option>
                  @foreach ($NonConformitysSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->code }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-2">
                <label>{{ __('general_content.problem_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="pb_descp"  placeholder="..." required></textarea>
              </div>
              <div class="col-2">
                <label>{{ __('general_content.proposal_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="proposal"  placeholder="..." required></textarea>
              </div>
              <div class="col-2">
                <label>{{ __('general_content.customer_reply_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="reply"  placeholder="..." required></textarea>
              </div>
              <div class="col-2">
                <label>{{ __('general_content.decision_description_trans_key') }}</label>
                <textarea class="form-control" rows="3" name="decision"  placeholder="..." required></textarea>
              </div>
              
            <!-- /.row -->
            </div>
          </div>
          <div class="card-footer">
            <div class="form-group row">
              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          <!-- /.row -->
          </div>
        </form>
        <!-- /.card-body -->
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="NonConformities">
      <div class="card-body">
        <x-InfocalloutComponent note="{{ __('general_content.non_conformitie_note_trans_key') }}"  />
        @include('include.alert-result')
        <div class="row">
          <table  class="table  table-striped">
            <thead>
            <tr>
              <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.type_trans_key') }}</th>
                <th>{{__('general_content.status_trans_key') }}</th>
                <th>{{__('general_content.customer_trans_key') }}</th>
                <th>{{ __('general_content.service_trans_key') }}</th>
                <th>{{ __('general_content.failure_trans_key') }}</th>
                <th>{{ __('general_content.cause_trans_key') }}</th>
                <th>{{ __('general_content.correction_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
              @forelse ($QualityNonConformitys as $QualityNonConformity)
              <tr>
                <td>{{ $QualityNonConformity->code }}</td>
                <td>{{ $QualityNonConformity->label }}</td>
                <td>{{ $QualityNonConformity->UserManagement['name'] }}</td>
                <td>
                  @if($QualityNonConformity->type  == 1) <span class="badge badge-warning">{{ __('general_content.internal_trans_key') }}</span> @endif
                  @if($QualityNonConformity->type  == 2) <span class="badge badge-danger">{{ __('general_content.external_trans_key') }}</span> @endif
                </td>
                <td>
                  @if($QualityNonConformity->statu  == 1) <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                  @if($QualityNonConformity->statu  == 2) <span class="badge badge-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @endif
                  @if($QualityNonConformity->statu  == 3) <span class="badge badge-success">{{ __('general_content.validate_trans_key') }}</span> @endif
                  @if($QualityNonConformity->statu  == 4) <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span> @endif
                </td>
                <td><x-CompanieButton id="{{ $QualityNonConformity->companie_id }}" label="{{ $QualityNonConformity->companie->label }}"  /></td>
                <td>{{ $QualityNonConformity->service->label }}</td>
                <td>{{ $QualityNonConformity->Failure->label }}</td>
                <td>{{ $QualityNonConformity->Cause->label }}</td>
                <td>{{ $QualityNonConformity->Correction->label }}</td>
                
                <td>{{ $QualityNonConformity->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityNonConformityView{{ $QualityNonConformity->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityNonConformity->id }} -->
                  <x-adminlte-modal id="QualityNonConformityView{{ $QualityNonConformity->id }}" title="Info {{ $QualityNonConformity->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    <div class="row">
                        <strong>{{__('general_content.failure_comment_trans_key') }} : </strong> 
                        {{ $QualityNonConformity->failure_comment }}
                    </div>
                    <div class="row">
                      <strong >{{__('general_content.cause_comment_trans_key') }} :</strong> 
                      {{ $QualityNonConformity->causes_comment }}
                    </div>
                    <div class="row">
                        <strong >{{__('general_content.correction_comment_trans_key') }}  :</strong> 
                        {{ $QualityNonConformity->correction_comment }}
                    </div>
                  </x-adminlte-modal>

                  <!-- Button Modal -->
                  <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityNonConformity{{ $QualityNonConformity->id }}">
                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                  </button>
                  <!-- Modal {{ $QualityNonConformity->id }} -->
                  <x-adminlte-modal id="QualityNonConformity{{ $QualityNonConformity->id }}" title="Update {{ $QualityNonConformity->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                    <form method="POST" action="{{ route('quality.nonConformitie.update', ['id' => $QualityNonConformity->id]) }}" enctype="multipart/form-data">
                      @csrf
                      <div class="card-body">
                        <div class="form-group">
                          <label for="label">{{__('general_content.label_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityNonConformity->label }}">
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                            </div>
                            <select class="form-control" name="statu" id="statu">
                              <option value="1"  @if($QualityNonConformity->statu == 1  ) Selected @endif>{{ __('general_content.in_progress_trans_key') }}</option>
                              <option value="2"  @if($QualityNonConformity->statu == 2  ) Selected @endif>{{ __('general_content.waiting_customer_data_trans_key') }}</option>
                              <option value="3"  @if($QualityNonConformity->statu == 3  ) Selected @endif>{{ __('general_content.validate_trans_key') }}</option>
                              <option value="4"  @if($QualityNonConformity->statu == 4  ) Selected @endif>{{ __('general_content.canceled_trans_key') }}</option>
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
                              <option value="1" @if($QualityNonConformity->type == 1  ) Selected @endif>{{ __('general_content.internal_trans_key') }}</option>
                              <option value="2" @if($QualityNonConformity->type == 2  ) Selected @endif>{{ __('general_content.external_trans_key') }}</option>
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
                              <option value="{{ $item->id }}"  @if($QualityNonConformity->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="service_id">{{ __('general_content.service_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control" name="service_id" id="service_id">
                              @foreach ($ServicesSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="companie_id">{{ __('general_content.companie_concern_trans_key') }}</label>
                          <select class="form-control" name="companie_id" id="companie_id">
                            @foreach ($CompaniesSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityNonConformity->companie_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="row">
                          <div class="col-4">
                            <label for="failure_id">{{ __('general_content.failure_type_trans_key') }}</label>
                            <select class="form-control" name="failure_id" id="failure_id">
                              @foreach ($FailuresSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->failure_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-4">
                            <label for="causes_id">{{ __('general_content.cause_type_trans_key') }}</label>
                            <select class="form-control" name="causes_id" id="causes_id">
                              @foreach ($CausesSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->causes_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-4">
                            <label for="correction_id">{{ __('general_content.correction_type_trans_key') }}</label> 
                            <select class="form-control" name="correction_id" id="correction_id">
                              @foreach ($CorrectionsSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityNonConformity->correction_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-4">
                            <label>{{ __('general_content.failure_comment_trans_key') }}</label>
                            <textarea class="form-control" rows="3" name="failure_comment"  placeholder="..." required>{{ $QualityNonConformity->failure_comment}}</textarea>
                          </div>
                          <div class="col-4">
                              <label>{{ __('general_content.cause_comment_trans_key') }}</label>
                              <textarea class="form-control" rows="3" name="causes_comment"  placeholder="..." required>{{ $QualityNonConformity->causes_comment}}</textarea>
                          </div>
                          <div class="col-4">
                            <label>{{ __('general_content.correction_comment_trans_key') }}</label> 
                            <textarea class="form-control" rows="3" name="correction_comment"  placeholder="..." required>{{ $QualityNonConformity->correction_comment}}</textarea>
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
              <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}"  />
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.type_trans_key') }}</th>
                <th>{{__('general_content.status_trans_key') }}</th>
                <th>{{__('general_content.customer_trans_key') }}</th>
                <th>{{ __('general_content.service_trans_key') }}</th>
                <th>{{ __('general_content.failure_trans_key') }}</th>
                <th>{{ __('general_content.cause_trans_key') }}</th>
                <th>{{ __('general_content.correction_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="col-5">
          {{ $QualityNonConformitys->links() }}
          </div>
        <!-- /.row -->
        </div>
      <!-- /.card-body -->
      </div>
      <hr>
      <div class="card card-secondary">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.new_non_conformitie_trans_key') }}</h3>
        </div>
        <form method="POST" action="{{ route('quality.nonConformitie.create')}}" > 
          <div class="card-body">
            @csrf
            <div class="form-group row">
              <div class="col-4">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="NC-{{ $LastNonConformity->id ?? '0' }}  ">
                </div>    
              </div>
              <div class="col-4">
                <label for="label">{{__('general_content.label_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
                </div>
              </div>
              <div class="col-4">
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
              <div class="col-4">
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
              <div class="col-4">
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
              <div class="col-4">
                <label for="service_id">{{ __('general_content.service_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                  </div>
                  <select class="form-control" name="service_id" id="service_id">
                    @foreach ($ServicesSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-12">
                <label for="companie_id">{{ __('general_content.companie_concern_trans_key') }}</label>
                <select class="form-control" name="companie_id" id="companie_id">
                  @foreach ($CompaniesSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12">
                <div class="row">
                  <div class="col-4">
                    <label for="failure_id">{{ __('general_content.failure_type_trans_key') }}</label>
                    <select class="form-control" name="failure_id" id="failure_id">
                      @foreach ($FailuresSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-4">
                    <label for="causes_id">{{ __('general_content.cause_type_trans_key') }}</label>
                    <select class="form-control" name="causes_id" id="causes_id">
                      @foreach ($CausesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-4">
                    <label for="correction_id">{{ __('general_content.correction_type_trans_key') }}</label>
                    <select class="form-control" name="correction_id" id="correction_id">
                      @foreach ($CorrectionsSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="row">
                  <div class="col-4">
                    <label>{{ __('general_content.failure_comment_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="failure_comment"  placeholder="..." required></textarea>
                  </div>
                  <div class="col-4">
                    <label>{{ __('general_content.cause_comment_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="causes_comment"  placeholder="..." required></textarea>
                  </div>
                  <div class="col-4">
                    <label>{{ __('general_content.correction_comment_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="correction_comment"  placeholder="..." required></textarea>
                  </div>
                </div>
              </div>
            <!-- /.row -->
            </div>
          </div>
          <div class="card-footer">
            <div class="form-group row">
              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
            </div>
          <!-- /.row -->
          </div>
        </form>
        <!-- /.card-body -->
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="MeasuringDevices">
      <x-InfocalloutComponent note="{{ __('general_content.measuring_devices_note_trans_key') }}"  />
      @include('include.alert-result')
      <div class="row">
        <div class="col-md-7 card-primary">
          <div class="card-header">
              <h3 class="card-title">{{ __('general_content.measuring_devices_trans_key') }}</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover">
              <thead>
              <tr>
                <th>{{ __('general_content.external_id_trans_key') }}</th>
                <th>{{__('general_content.label_trans_key') }}</th>
                <th>{{ __('general_content.ressource_trans_key') }}</th>
                <th>{{ __('general_content.user_trans_key') }}</th>
                <th>{{ __('general_content.serial_number_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th></th>
              </tr>
              </thead>
              <tbody>
                @forelse ($QualityControlDevices as $QualityControlDevice)
                <tr>
                  <td>{{ $QualityControlDevice->code }}</td>
                  <td>{{ $QualityControlDevice->label }}</td>
                  <td>{{ $QualityControlDevice->service['label'] }}</td>
                  <td>{{ $QualityControlDevice->UserManagement['name'] }}</td>
                  <td>{{ $QualityControlDevice->serial_number }}</td>
                  <td>{{ $QualityControlDevice->GetPrettyCreatedAttribute() }}</td>
                  <td>
                    <!-- Button Modal -->
                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityControlDevice{{ $QualityControlDevice->id }}">
                      <i class="fa fa-lg fa-fw  fa-edit"></i>
                    </button>
                    <!-- Modal {{ $QualityControlDevice->id }} -->
                    <x-adminlte-modal id="QualityControlDevice{{ $QualityControlDevice->id }}" title="Update {{ $QualityControlDevice->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                      <form method="POST" action="{{ route('quality.device.update', ['id' => $QualityControlDevice->id]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                          <div class="col-6">
                            <label for="label">{{__('general_content.label_trans_key') }}</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityControlDevice->label }}">
                            </div>
                          </div>
                          <div class="col-6">
                            <label for="service_id">{{ __('general_content.service_trans_key') }}</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                              </div>
                              <select class="form-control" name="service_id" id="service_id">
                                @foreach ($ServicesSelect as $item)
                                <option value="{{ $item->id }}"  @if($QualityControlDevice->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-6">
                            <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                              </div>
                              <select class="form-control" name="user_id" id="user_id">
                                @foreach ($userSelect as $item)
                                <option value="{{ $item->id }}" @if($QualityControlDevice->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <div class="col-6">
                            <label for="label">{{ __('general_content.serial_number_trans_key') }}</label>
                            <input type="text" class="form-control"  name="serial_number" id="serial_number" placeholder="{{ __('general_content.serial_number_trans_key') }}" value="{{ $QualityControlDevice->serial_number }}">
                          </div>
                        <!-- /.row -->
                        </div>
                        <div class="form-group">
                          <div class="col-md-12">
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
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{__('general_content.label_trans_key') }}</th>
                  <th>{{ __('general_content.ressource_trans_key') }}</th>
                  <th>{{ __('general_content.user_trans_key') }}</th>
                  <th>{{ __('general_content.serial_number_trans_key') }}</th>
                  <th>{{__('general_content.created_at_trans_key') }}</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          <!-- /.card-body -->
          </div>
          <div class="row">
            <div class="col-5">
            {{ $QualityControlDevices->links() }}
            </div>
          <!-- /.row -->
          </div>
        <!-- /.col-md-7 card-primary -->
        </div>
        <div class="col-md-5 card-secondary">
          <div class="card-header">
            <h3 class="card-title">{{ __('general_content.new_measuring_devices_trans_key') }}</h3>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('quality.device.create')}}" enctype="multipart/form-data">
              @csrf
              <div class="row">
                <div class="col-4">
                  <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                    <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                  </div>
                </div>
                <div class="col-4">
                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
                  </div>
                </div>
                <div class="col-4">
                  <label for="service_id">{{ __('general_content.service_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-list"></i></span>
                    </div>
                    <select class="form-control" name="service_id" id="service_id">
                      @foreach ($ServicesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-4">
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
                <div class="col-4">
                  <label for="label">{{ __('general_content.serial_number_trans_key') }}</label>
                  <input type="text" class="form-control"  name="serial_number" id="serial_number" placeholder="{{ __('general_content.serial_number_trans_key') }}">
                </div>
              <!-- /.row -->
              </div>
              <div class="form-group">
                <div class="col-md-8">
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
                </div>
              <!-- /.form-group -->
              </div>
              <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </div>
            </form>
          </div>
          <!-- /.card body -->
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Settings">
      <x-InfocalloutComponent note="{{ __('general_content.quality_setting_note_trans_key') }}"  />
      @include('include.alert-result')
      <div class="card card-primary collapsed-card">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.failure_trans_key') }}</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="{{ __('general_content.collapse_trans_key') }}">
              <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="{{ __('general_content.remove_trans_key') }}">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="card-body" style="display: none;">
          <div class="row">
            <div class="col-md-6 card-secondary">
              <div class="card-header">
                  <h3 class="card-title">{{ __('general_content.failure_type_trans_key') }}</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('general_content.external_id_trans_key') }}</th>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($QualityFailures as $QualityFailure)
                    <tr>
                      <td>{{ $QualityFailure->code }}</td>
                      <td>{{ $QualityFailure->label }}</td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityFailure{{ $QualityFailure->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $QualityFailure->id }} -->
                        <x-adminlte-modal id="QualityFailure{{ $QualityFailure->id }}" title="Update {{ $QualityFailure->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('quality.failure.update', ['id' => $QualityFailure->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityFailure->label }}">
                                </div>
                              </div>
                            </div>
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                      <x-EmptyDataLine col="3" text=" {{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                </table>
              </div>
            <!-- /.card secondary -->
            </div>
            <div class="col-md-6 card-secondary">
                <div class="card-header">
                  <h3 class="card-title">{{ __('general_content.new_failure_trans_key') }}</h3>
                </div>
                <form  method="POST" action="{{ route('quality.failure.create') }}" class="form-horizontal">
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
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
            <!-- /.card secondary -->
            </div>
          <!-- /.row -->
          </div>
        <!-- /.card body -->
        </div>
      <!-- /.card primary -->
      </div>
      <div class="card card-primary collapsed-card">
          <div class="card-header">
            <h3 class="card-title">{{ __('general_content.cause_trans_key') }}</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="{{ __('general_content.collapse_trans_key') }}">
                <i class="fas fa-plus"></i>
              </button>
              <button type="button" class="btn btn-tool" data-card-widget="remove" title="{{ __('general_content.remove_trans_key') }}">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="card-body" style="display: none;">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('general_content.cause_type_trans_key') }}</h3>
                </div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>{{ __('general_content.external_id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($QualityCauses as $QualityCause)
                      <tr>
                        <td>{{ $QualityCause->code }}</td>
                        <td>{{ $QualityCause->label }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button Modal -->
                          <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityCause{{ $QualityCause->id }}">
                            <i class="fa fa-lg fa-fw  fa-edit"></i>
                          </button>
                          <!-- Modal {{ $QualityCause->id }} -->
                          <x-adminlte-modal id="QualityCause{{ $QualityCause->id }}" title="Update {{ $QualityCause->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                            <form method="POST" action="{{ route('quality.cause.update', ['id' => $QualityCause->id]) }}" enctype="multipart/form-data">
                              @csrf
                              <div class="card-body">
                                <div class="form-group">
                                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityCause->label }}">
                                  </div>
                                </div>
                              </div>
                              <div class="card-footer">
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                              </div>
                            </form>
                          </x-adminlte-modal>
                        </td>
                      </tr>
                      @empty
                      <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                      @endforelse
                    </tbody>
                  </table>
                </div>
              <!-- /.card secondary -->
              </div>
              <div class="col-md-6 card-secondary">
                  <div class="card-header">
                    <h3 class="card-title">{{ __('general_content.new_cause_trans_key') }}</h3>
                  </div>
                  <form  method="POST" action="{{ route('quality.cause.create') }}" class="form-horizontal">
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
                    <div class="card-footer">
                      <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                  </form>
              <!-- /.card secondary -->
              </div>
            <!-- /.row -->
            </div>
          <!-- /.card body -->
          </div>
          <!-- /.card primary -->
      </div>
      <div class="card card-primary collapsed-card">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.correction_trans_key') }}</h3> 
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="{{ __('general_content.collapse_trans_key') }}">
              <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="{{ __('general_content.remove_trans_key') }}">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="card-body" style="display: none;">
          <div class="row">
            <div class="col-md-6 card-secondary">
              <div class="card-header">
                  <h3 class="card-title">{{ __('general_content.correction_type_trans_key') }}</h3> 
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('general_content.external_id_trans_key') }}</th>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($QualityCorrections as $QualityCorrection)
                    <tr>
                      <td>{{ $QualityCorrection->code }}</td>
                      <td>{{ $QualityCorrection->label }}</td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QualityCorrection{{ $QualityCorrection->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $QualityCorrection->id }} -->
                        <x-adminlte-modal id="QualityCorrection{{ $QualityCorrection->id }}" title="Update {{ $QualityCorrection->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('quality.correction.update', ['id' => $QualityCorrection->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityCorrection->label }}">
                                </div>
                              </div>
                            </div>
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                      <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                </table>
              </div>
            <!-- /.card secondary -->
            </div>
            <div class="col-md-6 card-secondary">
                <div class="card-header">
                  <h3 class="card-title">{{ __('general_content.new_correction_trans_key') }}</h3>
                </div>
                <form  method="POST" action="{{ route('quality.correction.create') }}" class="form-horizontal">
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
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
            <!-- /.card secondary -->
            </div>
          <!-- /.row -->
          </div>
        <!-- /.card body -->
        </div>
      <!-- /.card primary -->
      </div>
    <!-- /.tab-pane -->
    </div>
  <!-- /.tab-content -->
  </div>
<!-- /.card -->
</div>
@stop

@section('css')
@stop

@section('js')
@stop