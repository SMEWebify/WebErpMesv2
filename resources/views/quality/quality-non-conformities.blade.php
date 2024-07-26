@extends('adminlte::page')

@section('title', __('general_content.non_conformities_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.non_conformities_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

      <x-InfocalloutComponent note="{{ __('general_content.non_conformitie_note_trans_key') }}"  />
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
                <th>{{__('general_content.customer_trans_key') }}</th>
                <th>{{__('general_content.order_trans_key') }}</th>
                <th>{{__('general_content.task_trans_key') }}</th>
                <th>{{__('general_content.delivery_notes_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th>{{__('general_content.action_trans_key') }}</th>
            </tr>
            </thead>
            <tbody>
              @forelse ($QualityNonConformitys as $QualityNonConformity)
              <tr>
                <td>{{ $QualityNonConformity->code }}</td>
                <td>{{ $QualityNonConformity->label }}</td>
                <td><img src="{{ Avatar::create($QualityNonConformity->UserManagement['name'])->toBase64() }}" /></td>
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
                <td>
                  @if($QualityNonConformity->companie_id)
                    <x-CompanieButton id="{{ $QualityNonConformity->companie_id }}" label="{{ $QualityNonConformity->companie->label }}"  />
                  @else
                      N/A
                  @endif
                </td>
                <td>
                  @if($QualityNonConformity->order_lines_id)
                  <x-OrderButton id="{{ $QualityNonConformity->orderLine->orders_id }}" code="{{ $QualityNonConformity->orderLine->order['code'] }}"  />
                  @else
                  N/A
                  @endif
                </td>
                <td>
                  @if($QualityNonConformity->task_id)
                  <a href="{{ route('production.task.statu.id', ['id' => $QualityNonConformity->task_id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                  @else
                  N/A
                  @endif
                </td>
                <td>
                  @if($QualityNonConformity->deliverys_id)
                  <a href="{{ route('deliverys.show', ['id' => $QualityNonConformity->deliverys_id]) }}" class="btn btn-sm btn-primary">{{ $QualityNonConformity->delivery['code'] }}</a>
                  @else
                  N/A
                  @endif
                </td>
                <td>{{ $QualityNonConformity->GetPrettyCreatedAttribute() }}</td>
                <td class=" py-0 align-middle">
                  <!-- Button Modal -->
                  <button type="button" class="btn bg-info" data-toggle="modal" data-target="#QualityNonConformityView{{ $QualityNonConformity->id }}">
                    <i class="fa fa-lg fa-fw fa-eye"></i>
                  </button>
                  <!-- Modal {{ $QualityNonConformity->id }} -->
                  <x-adminlte-modal id="QualityNonConformityView{{ $QualityNonConformity->id }}" title="Info {{ $QualityNonConformity->label }}" theme="info" icon="fa fa-pen" size='lg' disable-animations>
                    
                    <div class="row">
                      <strong >{{__('general_content.failure_trans_key') }}  :</strong> 
                      @if($QualityNonConformity->failure_id)
                        {{ $QualityNonConformity->Failure->label }}
                      @else
                        N/A
                      @endif
                    </div>
                    <div class="row">
                        <strong>{{__('general_content.failure_comment_trans_key') }} : </strong> 
                        {{ $QualityNonConformity->failure_comment }}
                    </div>
                    <hr>
                    <div class="row">
                      <strong >{{__('general_content.cause_trans_key') }}  :</strong> 
                      @if($QualityNonConformity->causes_id)
                        {{ $QualityNonConformity->Cause->label }}
                      @else
                        N/A
                      @endif
                    </div>
                    <div class="row">
                      <strong >{{__('general_content.cause_comment_trans_key') }} :</strong> 
                      {{ $QualityNonConformity->causes_comment }}
                    </div>
                    <hr>
                    <div class="row">
                      <strong >{{__('general_content.correction_trans_key') }}  :</strong> 
                      @if($QualityNonConformity->correction_id)
                        {{ $QualityNonConformity->Correction->label }}
                      @else
                        N/A
                      @endif
                    </div>
                    <div class="row">
                        <strong >{{__('general_content.correction_comment_trans_key') }}  :</strong> 
                        {{ $QualityNonConformity->correction_comment }}
                    </div>
                    <hr>
                    <div class="row">
                      <strong >{{__('general_content.service_trans_key') }}  :</strong> 
                      @if($QualityNonConformity->service_id)
                        {{ $QualityNonConformity->service->label }}
                      @else
                        N/A
                      @endif
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
                              <option value="">N/A</option>
                              @foreach ($ServicesSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="companie_id">{{ __('general_content.companie_concern_trans_key') }}</label>
                          <select class="form-control" name="companie_id" id="companie_id" @if($QualityNonConformity->order_lines_id || $QualityNonConformity->task_id) disabled @endif>
                            @foreach ($CompaniesSelect as $item)
                            <option value="{{ $item->id }}"  @if($QualityNonConformity->companie_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="form-group">
                          <label for="label">{{__('general_content.qty_trans_key') }}</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                            <input type="number" class="form-control" name="qty"  id="qty" placeholder="{{__('general_content.qty_trans_key') }}" value="{{ $QualityNonConformity->qty }}">
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group col-md-4">
                            <label for="failure_id">{{ __('general_content.failure_type_trans_key') }}</label>
                            <select class="form-control" name="failure_id" id="failure_id">
                              <option value="">N/A</option>
                              @foreach ($FailuresSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->failure_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="form-group col-md-4">
                            <label for="causes_id">{{ __('general_content.cause_type_trans_key') }}</label>
                            <select class="form-control" name="causes_id" id="causes_id">
                              <option value="">N/A</option>
                              @foreach ($CausesSelect as $item)
                              <option value="{{ $item->id }}" @if($QualityNonConformity->causes_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="form-group col-md-4">
                            <label for="correction_id">{{ __('general_content.correction_type_trans_key') }}</label> 
                            <select class="form-control" name="correction_id" id="correction_id">
                              <option value="">N/A</option>
                              @foreach ($CorrectionsSelect as $item)
                              <option value="{{ $item->id }}"  @if($QualityNonConformity->correction_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                              @endforeach
                            </select>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group col-md-4">
                            <label>{{ __('general_content.failure_comment_trans_key') }}</label>
                            <textarea class="form-control" rows="3" name="failure_comment"  placeholder="..." >{{ $QualityNonConformity->failure_comment}}</textarea>
                          </div>
                          <div class="form-group col-md-4">
                              <label>{{ __('general_content.cause_comment_trans_key') }}</label>
                              <textarea class="form-control" rows="3" name="causes_comment"  placeholder="..." >{{ $QualityNonConformity->causes_comment}}</textarea>
                          </div>
                          <div class="form-group col-md-4">
                            <label>{{ __('general_content.correction_comment_trans_key') }}</label> 
                            <textarea class="form-control" rows="3" name="correction_comment"  placeholder="...">{{ $QualityNonConformity->correction_comment}}</textarea>
                          </div>
                        </div>
                      </div>
                      <div class="card-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                      </div>
                    </form>
                  </x-adminlte-modal>
                  
                  <x-ButtonTextPDF route="{{ route('pdf.nc', ['Document' => $QualityNonConformity->id])}}" />
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
                <th>{{__('general_content.order_trans_key') }}</th>
                <th>{{__('general_content.task_trans_key') }}</th>
                <th>{{__('general_content.delivery_notes_trans_key') }}</th>
                <th>{{__('general_content.created_at_trans_key') }}</th>
                <th>{{__('general_content.action_trans_key') }}</th>
              </tr>
            </tfoot>
          </table>
        <!-- /.row -->
        </div>
        <div class="row">
          <div class="form-group col-md-5">
          {{ $QualityNonConformitys->links() }}
          </div>
        <!-- /.row -->
        </div>
      </x-adminlte-card>
        <form method="POST" action="{{ route('quality.nonConformitie.create')}}" > 
          <x-adminlte-card title="{{ __('general_content.new_non_conformitie_trans_key') }}" theme="secondary" maximizable>
            @csrf
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                  <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="NC-{{ $LastNonConformity->id ?? '0' }}  ">
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
              <div class="form-group col-md-4">
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
              <div class="form-group col-md-4">
                <label for="service_id">{{ __('general_content.service_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                  </div>
                  <select class="form-control" name="service_id" id="service_id">
                    <option value="">N/A</option>
                    @foreach ($ServicesSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="form-group col-md-12">
                <label for="companie_id">{{ __('general_content.companie_concern_trans_key') }}</label>
                <select class="form-control" name="companie_id" id="companie_id">
                  @foreach ($CompaniesSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-12">
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="failure_id">{{ __('general_content.failure_type_trans_key') }}</label>
                    <select class="form-control" name="failure_id" id="failure_id">
                      <option value="">N/A</option>
                      @foreach ($FailuresSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="causes_id">{{ __('general_content.cause_type_trans_key') }}</label>
                    <select class="form-control" name="causes_id" id="causes_id">
                      <option value="">N/A</option>
                      @foreach ($CausesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="correction_id">{{ __('general_content.correction_type_trans_key') }}</label>
                    <select class="form-control" name="correction_id" id="correction_id">
                      <option value="">N/A</option>
                      @foreach ($CorrectionsSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->label }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="form-group col-md-12">
                <div class="row">
                  <div class="form-group col-md-4">
                    <label>{{ __('general_content.failure_comment_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="failure_comment"  placeholder="..." ></textarea>
                  </div>
                  <div class="form-group col-md-4">
                    <label>{{ __('general_content.cause_comment_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="causes_comment"  placeholder="..." ></textarea>
                  </div>
                  <div class="form-group col-md-4">
                    <label>{{ __('general_content.correction_comment_trans_key') }}</label>
                    <textarea class="form-control" rows="3" name="correction_comment"  placeholder="..."></textarea>
                  </div>
                </div>
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