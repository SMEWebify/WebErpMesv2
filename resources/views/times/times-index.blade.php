@extends('adminlte::page')

@section('title', __('general_content.times_setting_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.times_setting_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @include('include.alert-result')
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Absence" data-toggle="tab">{{ __('general_content.absence_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#BanckHoliday" data-toggle="tab">{{ __('general_content.banck_holiday_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#ImproductTime" data-toggle="tab">{{ __('general_content.improduct_time_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#MachineEvent" data-toggle="tab">{{ __('general_content.machine_event_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Absence">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.leave_request_trans_key') }}" theme="primary" maximizable>
            <div class="able-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{ __('general_content.type_trans_key') }}</th>
                    <th>{{ __('general_content.type_of_day_trans_key') }}</th>
                    <th>{{__('general_content.status_trans_key') }}</th>
                    <th>{{ __('general_content.start_date_trans_key') }}</th>
                    <th>{{ __('general_content.end_date_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($TimesAbsences as $TimesAbsence)
                  <tr>
                    <td>{{ $TimesAbsence->User['name'] }}</td>
                    <td>
                      @if($TimesAbsence->absence_type  == 1){{ __('general_content.full_day_absence_trans_key') }} @endif
                      @if($TimesAbsence->absence_type  == 2){{ __('general_content.1_half_day_absence_trans_key') }} @endif
                      @if($TimesAbsence->absence_type  == 3){{ __('general_content.2_half_day_absence_trans_key') }} @endif
                      @if($TimesAbsence->absence_type  == 4){{ __('general_content.absence_in_hours_trans_key') }} @endif
                    </td>
                    <td>
                      @if($TimesAbsence->absence_type_day  == 1){{ __('general_content.calendar_trans_key') }} @endif
                      @if($TimesAbsence->absence_type_day  == 2){{ __('general_content.workable_day_trans_key') }} @endif
                      @if($TimesAbsence->absence_type_day  == 3){{ __('general_content.worked_day_trans_key') }} @endif
                    </td>
                    <td>
                      @if($TimesAbsence->statu  == 1){{ __('general_content.to_validate_trans_key') }} @endif
                      @if($TimesAbsence->statu  == 2){{ __('general_content.validate_trans_key') }} @endif
                      @if($TimesAbsence->statu  == 3){{ __('general_content.unvalidate_trans_key') }} @endif
                    </td>
                    <td>{{ $TimesAbsence->start_date }}</td>
                    <td>{{ $TimesAbsence->end_date }}</td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#TimesAbsence{{ $TimesAbsence->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $TimesAbsence->id }} -->
                      <x-adminlte-modal id="TimesAbsence{{ $TimesAbsence->id }}" title="Update {{ $TimesAbsence->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('times.absence.update', ['id' => $TimesAbsence->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="user_id">{{ __('general_content.user_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" name="user_id" id="user_id">
                                  @foreach ($userSelect as $item)
                                  <option value="{{ $item->id }}" @if($TimesAbsence->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                  @endforeach
                                </select>
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="absence_type">{{ __('general_content.absence_type_trans_key') }}</label>
                              <select class="form-control" name="absence_type" id="absence_type">
                                  <option value="1" @if($TimesAbsence->absence_type == 1  ) Selected @endif>{{ __('general_content.full_day_absence_trans_key') }}</option>
                                  <option value="2" @if($TimesAbsence->absence_type == 2  ) Selected @endif>{{ __('general_content.1_half_day_absence_trans_key') }}</option>
                                  <option value="3" @if($TimesAbsence->absence_type == 3  ) Selected @endif>{{ __('general_content.2_half_day_absence_trans_key') }}</option>
                                  <option value="4" @if($TimesAbsence->absence_type == 4  ) Selected @endif>{{ __('general_content.absence_in_hours_trans_key') }}</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="absence_type_day">{{ __('general_content.absence_type_day_trans_key') }}</label>
                              <select class="form-control" name="absence_type_day" id="absence_type_day">
                                  <option value="1" @if($TimesAbsence->absence_type_day == 1  ) Selected @endif>{{ __('general_content.calendar_trans_key') }}</option>
                                  <option value="2" @if($TimesAbsence->absence_type_day == 2  ) Selected @endif>{{ __('general_content.workable_day_trans_key') }}</option>
                                  <option value="3" @if($TimesAbsence->absence_type_day == 3  ) Selected @endif>{{ __('general_content.worked_day_trans_key') }}</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                              <select class="form-control" name="statu" id="statu">
                                  <option value="1" @if($TimesAbsence->statu == 1  ) Selected @endif>{{ __('general_content.to_validate_trans_key') }}</option>
                                  <option value="2" @if($TimesAbsence->statu == 2  ) Selected @endif>{{ __('general_content.validate_trans_key') }}</option>
                                  <option value="3" @if($TimesAbsence->statu == 3  ) Selected @endif>{{ __('general_content.unvalidate_trans_key') }}</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="start_date">{{ __('general_content.start_date_trans_key') }}</label>
                              <input type="date" class="form-control" name="start_date"  id="start_date" value="{{ $TimesAbsence->start_date }}">
                            </div>
                            <div class="form-group">
                              <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                              <input type="date" class="form-control" name="end_date"  id="end_date" value="{{ $TimesAbsence->end_date }}">
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
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{ __('general_content.type_trans_key') }}</th>
                    <th>{{ __('general_content.type_of_day_trans_key') }}</th>
                    <th>{{__('general_content.status_trans_key') }}</th>
                    <th>{{ __('general_content.start_date_trans_key') }}</th>
                    <th>{{ __('general_content.end_date_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>
        <div class="col-md-6">
          <form  method="POST" action="{{ route('times.absence.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_absence_request_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
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
              <div class="form-group">
                <label for="absence_type">{{ __('general_content.absence_type_trans_key') }}</label>
                <select class="form-control" name="absence_type" id="absence_type">
                    <option value="1">{{ __('general_content.full_day_absence_trans_key') }}</option>
                    <option value="2">{{ __('general_content.1_half_day_absence_trans_key') }}</option>
                    <option value="3">{{ __('general_content.2_half_day_absence_trans_key') }}</option>
                    <option value="4">{{ __('general_content.absence_in_hours_trans_key') }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="absence_type_day">{{ __('general_content.absence_type_day_trans_key') }}</label>
                <select class="form-control" name="absence_type_day" id="absence_type_day">
                    <option value="1">{{ __('general_content.calendar_trans_key') }}</option>
                    <option value="2">{{ __('general_content.workable_day_trans_key') }}</option>
                    <option value="3">{{ __('general_content.worked_day_trans_key') }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="start_date">{{ __('general_content.start_date_trans_key') }}</label>
                <input type="date" class="form-control" name="start_date"  id="start_date" >
              </div>
              <div class="form-group">
                <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                <input type="date" class="form-control" name="end_date"  id="end_date" >
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane " id="BanckHoliday">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.banck_holiday_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{__('general_content.fixed_trans_key') }}</th>
                    <th>{{__('general_content.date_trans_key') }}</th>
                    <th>{{__('general_content.label_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($TimesBanckHolidays as $TimesBanckHoliday)
                  <tr>
                    <td>
                      @if($TimesBanckHoliday->fixed  == 1){{ __('general_content.yes_trans_key') }} @endif
                      @if($TimesBanckHoliday->fixed  == 2){{ __('general_content.no_trans_key') }} @endif
                    </td>
                    <td>{{ $TimesBanckHoliday->date }}</td>
                    <td>{{ $TimesBanckHoliday->label }}</td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#TimesBanckHoliday{{ $TimesBanckHoliday->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $TimesBanckHoliday->id }} -->
                      <x-adminlte-modal id="TimesBanckHoliday{{ $TimesBanckHoliday->id }}" title="Update {{ $TimesBanckHoliday->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('times.banckholiday.update', ['id' => $TimesBanckHoliday->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $TimesBanckHoliday->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="fixed">{{__('general_content.fixed_trans_key') }}</label>
                              <select class="form-control" name="fixed" id="fixed">
                                  <option value="2" @if($TimesBanckHoliday->fixed == 2 ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($TimesBanckHoliday->fixed == 1 ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="date">{{__('general_content.date_trans_key') }}</label>
                              <input type="date" class="form-control" name="date"  id="date" value="{{ $TimesBanckHoliday->date }}">
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
                    <th>{{__('general_content.fixed_trans_key') }}</th>
                    <th>{{__('general_content.date_trans_key') }}</th>
                    <th>{{__('general_content.label_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card secondary -->
        </div>
        <div class="col-md-6">
          <form  method="POST" action="{{ route('times.banckholiday.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_banck_holiday_trans_key') }}" theme="secondary" maximizable>
              @csrf
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
                <label for="fixed">{{__('general_content.fixed_trans_key') }}</label>
                <select class="form-control" name="fixed" id="fixed">
                    <option value="2">{{ __('general_content.no_trans_key') }}</option>
                    <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="date">{{__('general_content.date_trans_key') }}</label>
                <input type="date" class="form-control" name="date"  id="date" >
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="ImproductTime">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.improduct_time_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.machine_statu_trans_key') }}</th>
                    <th>{{__('general_content.resource_required_trans_key') }}</th>
                    <th>{{__('general_content.mask_time_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($TimesImproductTimes as $TimesImproductTime)
                  <tr>
                    <td>{{ $TimesImproductTime->label }}</td>
                    <td>{{ $TimesImproductTime->MachineEvent['label'] }}</td>
                    <td>
                      @if($TimesImproductTime->resources_required  == 1){{ __('general_content.yes_trans_key') }} @endif
                      @if($TimesImproductTime->resources_required  == 2){{ __('general_content.no_trans_key') }} @endif
                    </td>
                    <td>
                      @if($TimesImproductTime->mask_time  == 1){{ __('general_content.yes_trans_key') }} @endif
                      @if($TimesImproductTime->mask_time  == 2){{ __('general_content.no_trans_key') }} @endif
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#TimesImproductTime{{ $TimesImproductTime->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $TimesImproductTime->id }} -->
                      <x-adminlte-modal id="TimesImproductTime{{ $TimesImproductTime->id }}" title="Update {{ $TimesImproductTime->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('times.improducttime.update', ['id' => $TimesImproductTime->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $TimesImproductTime->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="times_machine_events_id">{{__('general_content.machine_statu_trans_key') }}</label>
                              <select class="form-control" name="times_machine_events_id" id="times_machine_events_id">
                                  @foreach ($TimesMachineEventsSelect as $item)
                                  <option value="{{ $item->id }}" @if($TimesImproductTime->times_machine_events_id == $item->id ) Selected @endif>{{ $item->label }}</option>
                                  @endforeach
                                </select>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="resources_required">{{__('general_content.resource_required_trans_key') }}</label>
                              <select class="form-control" name="resources_required" id="resources_required">
                                  <option value="2" @if($TimesImproductTime->resources_required == 2 ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                  <option value="1" @if($TimesImproductTime->resources_required == 1 ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="mask_time">{{__('general_content.mask_time_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                                </div>
                                <select class="form-control" name="mask_time" id="mask_time">
                                    <option value="2" @if($TimesImproductTime->mask_time == 2 ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                    <option value="1" @if($TimesImproductTime->mask_time == 1 ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
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
                    <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.machine_statu_trans_key') }}</th>
                    <th>{{__('general_content.resource_required_trans_key') }}</th>
                    <th>{{__('general_content.mask_time_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card primary -->
        </div>
        <div class="col-md-6">
          <form  method="POST" action="{{ route('times.improducttime.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_improduct_time_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="form-group">
                <label for="label">{{__('general_content.label_trans_key') }}</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" >
                </div>
              </div>
              <div class="form-group">
                <label for="times_machine_events_id">{{__('general_content.machine_statu_trans_key') }}</label>
                <select class="form-control" name="times_machine_events_id" id="times_machine_events_id">
                    @foreach ($TimesMachineEventsSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @endforeach
                  </select>
                </select>
              </div>
              <div class="form-group">
                <label for="resources_required">{{__('general_content.resource_required_trans_key') }}</label>
                <select class="form-control" name="resources_required" id="resources_required">
                    <option value="2">{{ __('general_content.no_trans_key') }}</option>
                    <option value="1">{{ __('general_content.yes_trans_key') }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="mask_time">{{__('general_content.mask_time_trans_key') }}</label>
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
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="MachineEvent">
      <div class="row">
        <div class="col-md-6">
          <x-adminlte-card title="{{ __('general_content.machine_event_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.mask_time_trans_key') }}</th>
                    <th>{{ __('general_content.color_trans_key') }}</th>
                    <th>{{__('general_content.status_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($TimesMachineEvents as $TimesMachineEvent)
                  <tr>
                    <td>{{ $TimesMachineEvent->code }}</td>
                    <td>{{ $TimesMachineEvent->ordre }}</td>
                    <td>{{ $TimesMachineEvent->label }}</td>
                    <td>
                      @if($TimesMachineEvent->mask_time  == 1){{ __('general_content.yes_trans_key') }} @endif 
                      @if($TimesMachineEvent->mask_time  == 2){{ __('general_content.no_trans_key') }} @endif
                    </td>
                    <td><input type="color" class="form-control"  name="color" id="color" value="{{ $TimesMachineEvent->color }}"></td>
                    <td>
                      @if($TimesMachineEvent->etat  == 1){{ __('general_content.stop_trans_key') }} @endif
                      @if($TimesMachineEvent->etat  == 2){{ __('general_content.setup_trans_key') }} @endif
                      @if($TimesMachineEvent->etat  == 3){{ __('general_content.run_trans_key') }} @endif
                      @if($TimesMachineEvent->etat  == 4){{ __('general_content.off_trans_key') }} @endif
                    </td>
                    <td class=" py-0 align-middle">
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#TimesMachineEvent{{ $TimesMachineEvent->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $TimesMachineEvent->id }} -->
                      <x-adminlte-modal id="TimesMachineEvent{{ $TimesMachineEvent->id }}" title="Update {{ $TimesMachineEvent->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('times.machineevent.update', ['id' => $TimesMachineEvent->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                </div>
                                <input type="number" class="form-control" name="ordre" id="ordre" placeholder="10" value="{{ $TimesMachineEvent->ordre }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $TimesMachineEvent->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="mask_time">{{__('general_content.mask_time_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                                </div>
                                <select class="form-control" name="mask_time" id="mask_time">
                                    <option value="2"  @if($TimesMachineEvent->mask_time == 2 ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                    <option value="1"  @if($TimesMachineEvent->mask_time == 1 ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                                </select>
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="color">{{ __('general_content.color_trans_key') }}</label>
                              <input type="color" class="form-control"  name="color" id="color" value="{{ $TimesMachineEvent->color }}">
                            </div>
                            <div class="form-group">
                              <label for="etat">{{ __('general_content.status_trans_key') }}</label>
                              <select class="form-control" name="etat" id="etat">
                                  <option value="1" @if($TimesMachineEvent->etat == 1 ) Selected @endif>{{ __('general_content.stop_trans_key') }}</option>
                                  <option value="2" @if($TimesMachineEvent->etat == 2 ) Selected @endif>{{ __('general_content.setup_trans_key') }}</option>
                                  <option value="3" @if($TimesMachineEvent->etat == 3 ) Selected @endif>{{ __('general_content.run_trans_key') }}</option>
                                  <option value="4" @if($TimesMachineEvent->etat == 4 ) Selected @endif>{{ __('general_content.off_trans_key') }}</option>
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
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.mask_time_trans_key') }}</th>
                    <th>{{ __('general_content.color_trans_key') }}</th>
                    <th>{{__('general_content.status_trans_key') }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
        <!-- /.card primary -->
        </div>
        <div class="col-md-6">
          <form  method="POST" action="{{ route('times.machineevent.create') }}" class="form-horizontal">
            <x-adminlte-card title="{{ __('general_content.new_machine_event_trans_key') }}" theme="secondary" maximizable>
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
                <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                  </div>
                  <input type="number" class="form-control" name="ordre" id="ordre" placeholder="10">
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
                <label for="mask_time">{{__('general_content.mask_time_trans_key') }}</label>
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
                <label for="color">{{ __('general_content.color_trans_key') }}</label>
                <input type="color" class="form-control"  name="color" id="color" >
              </div>
              <div class="form-group">
                <label for="etat">{{ __('general_content.status_trans_key') }}</label>
                <select class="form-control" name="etat" id="etat">
                    <option value="1">{{ __('general_content.stop_trans_key') }}</option>
                    <option value="2">{{ __('general_content.setup_trans_key') }}</option>
                    <option value="3">{{ __('general_content.run_trans_key') }}</option>
                    <option value="4">{{ __('general_content.off_trans_key') }}</option>
                </select>
              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
  </div>
  <!-- /.tab-content -->
</div>
<!-- /.card -->

@stop

@section('css')
@stop

@section('js')
@stop