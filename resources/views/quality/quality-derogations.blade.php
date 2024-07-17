@extends('adminlte::page')

@section('title', __('general_content.derogations_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.derogations_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

      @include('include.alert-result')
      
      <x-adminlte-card theme="primary" theme-mode="outline">
        <div class=" table-responsive p-0">
          <table class="table  table-striped table-hover">
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
                <td><img src="{{ Avatar::create($QualityDerogation->UserManagement['name'])->toBase64() }}" /></td>
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
          <div class="form-group col-md-5">
          {{ $QualityDerogations->links() }}
          </div>
        <!-- /.row -->
        </div>
      </x-adminlte-card>

      <form method="POST" action="{{ route('quality.derogation.create')}}" >  
        <x-adminlte-card title="{{ __('general_content.new_derogation_trans_key') }}" theme="secondary" maximizable>
          @csrf
          <div class="form-row">
            <div class="form-group col-md-2">
              <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="DER-{{ $LastDerogation->id ?? '0' }}  ">
              </div>
            </div>
            <div class="form-group col-md-2">
              <label for="label">{{__('general_content.label_trans_key') }}</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
              </div>
            </div>
            <div class="form-group col-md-2">
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
            <div class="form-group col-md-2">
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
            <div class="form-group col-md-2">
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
            <div class="form-group col-md-2">
              <label for="quality_non_conformitie_id">{{ __('general_content.non_conformitie_trans_key') }}</label>
              <select class="form-control" name="quality_non_conformitie_id" id="quality_non_conformitie_id">
                <option value="null">-</option>
                @foreach ($NonConformitysSelect as $item)
                <option value="{{ $item->id }}">{{ $item->code }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>{{ __('general_content.problem_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="pb_descp"  placeholder="..." required></textarea>
            </div>
            <div class="form-group col-md-2">
              <label>{{ __('general_content.proposal_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="proposal"  placeholder="..." required></textarea>
            </div>
            <div class="form-group col-md-2">
              <label>{{ __('general_content.customer_reply_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="reply"  placeholder="..." required></textarea>
            </div>
            <div class="form-group col-md-2">
              <label>{{ __('general_content.decision_description_trans_key') }}</label>
              <textarea class="form-control" rows="3" name="decision"  placeholder="..." required></textarea>
            </div>
            
          <!-- /.row -->
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>
@stop

@section('css')
@stop

@section('js')
@stop