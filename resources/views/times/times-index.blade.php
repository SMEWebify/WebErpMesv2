@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Times setting</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @include('include.alert-result')
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Absence" data-toggle="tab">Absence</a></li>
      <li class="nav-item"><a class="nav-link" href="#BanckHoliday" data-toggle="tab">Banck holiday</a></li>
      <li class="nav-item"><a class="nav-link" href="#ImproductTime" data-toggle="tab">Improduct time</a></li>
      <li class="nav-item"><a class="nav-link" href="#MachineEvent" data-toggle="tab">Machine event</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Absence">
      <div class="row">
        <div class="col-md-6 card-primary">
          <div class="card-header">
              <h3 class="card-title">Absence Request</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Type</th>
                  <th>Type of day</th>
                  <th>Statu</th>
                  <th>Start date</th>
                  <th>End date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($TimesAbsences as $TimesAbsence)
                <tr>
                  <td>{{ $TimesAbsence->User['name'] }}</td>
                  <td>
                    @if($TimesAbsence->absence_type  == 1)Full day absence @endif
                    @if($TimesAbsence->absence_type  == 2)1 st half day absence @endif
                    @if($TimesAbsence->absence_type  == 3)2 nd half day absence @endif
                    @if($TimesAbsence->absence_type  == 4)Absence in hours @endif
                  </td>
                  <td>
                    @if($TimesAbsence->absence_type_day  == 1)Calendar @endif
                    @if($TimesAbsence->absence_type_day  == 2)Workable day @endif
                    @if($TimesAbsence->absence_type_day  == 3)Worked day @endif
                  </td>
                  <td>{{ $TimesAbsence->statu }}</td>
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
                            <label for="absence_type">Absence type</label>
                            <select class="form-control" name="absence_type" id="absence_type">
                                <option value="1" @if($TimesAbsence->absence_type == 1  ) Selected @endif>Full day absence</option>
                                <option value="2" @if($TimesAbsence->absence_type == 2  ) Selected @endif>1 st half day absence</option>
                                <option value="3" @if($TimesAbsence->absence_type == 3  ) Selected @endif>2 nd half day absence</option>
                                <option value="4" @if($TimesAbsence->absence_type == 4  ) Selected @endif>Absence in hours</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="absence_type_day">Absence type day</label>
                            <select class="form-control" name="absence_type_day" id="absence_type_day">
                                <option value="1" @if($TimesAbsence->absence_type_day == 1  ) Selected @endif>Calendar</option>
                                <option value="2" @if($TimesAbsence->absence_type_day == 2  ) Selected @endif>Workable day</option>
                                <option value="3" @if($TimesAbsence->absence_type_day == 3  ) Selected @endif>Worked day</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="start_date">Start date</label>
                            <input type="date" class="form-control" name="start_date"  id="start_date" value="{{ $TimesAbsence->start_date }}">
                          </div>
                          <div class="form-group">
                            <label for="end_date">End date</label>
                            <input type="date" class="form-control" name="end_date"  id="end_date" value="{{ $TimesAbsence->end_date }}">
                          </div>
                        </div>
                        <div class="card-footer">
                          <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                        </div>
                      </form>
                    </x-adminlte-modal>
                  </td>
                </tr>
                @empty
                  <x-EmptyDataLine col="7" text="No lines found ..."  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>User</th>
                  <th>Type</th>
                  <th>Type of day</th>
                  <th>Statu</th>
                  <th>Start date</th>
                  <th>End date</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <!-- /.card secondary -->
        </div>
        <div class="col-md-6 card-secondary">
          <div class="card-header">
            <h3 class="card-title">New absence request</h3>
          </div>
          <form  method="POST" action="{{ route('times.absence.create') }}" class="form-horizontal">
            @csrf
            <div class="form-group">
              <label for="absence_type">Absence type</label>
              <select class="form-control" name="absence_type" id="absence_type">
                  <option value="1">Full day absence</option>
                  <option value="2">1 st half day absence</option>
                  <option value="3">2 nd half day absence</option>
                  <option value="4">Absence in hours</option>
              </select>
              <input type="hidden" name="user_id" id="user_id" value="{{ $user->id }}">
            </div>
            <div class="form-group">
              <label for="absence_type_day">Absence type day</label>
              <select class="form-control" name="absence_type_day" id="absence_type_day">
                  <option value="1">Calendar</option>
                  <option value="2">Workable day</option>
                  <option value="3">Worked day</option>
              </select>
            </div>
            <div class="form-group">
              <label for="start_date">Start date</label>
              <input type="date" class="form-control" name="start_date"  id="start_date" >
            </div>
            <div class="form-group">
              <label for="end_date">End date</label>
              <input type="date" class="form-control" name="end_date"  id="end_date" >
            </div>
            <div class="card-footer">
              <div class="offset-sm-2 col-sm-10">
                <button type="submit" class="btn btn-danger">Submit New</button>
              </div>
            </div>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane " id="BanckHoliday">
      <div class="row">
        <div class="col-md-6 card-primary">
          <div class="card-header">
              <h3 class="card-title">Banck Holidays list</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Fixed</th>
                  <th>date</th>
                  <th>label</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($TimesBanckHolidays as $TimesBanckHoliday)
                <tr>
                  <td>
                    @if($TimesBanckHoliday->fixed  == 1)Yes @endif
                    @if($TimesBanckHoliday->fixed  == 2)No @endif
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
                            <label for="label">Label</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $TimesBanckHoliday->label }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="fixed">Fixed</label>
                            <select class="form-control" name="fixed" id="fixed">
                                <option value="2" @if($TimesBanckHoliday->fixed == 2 ) Selected @endif>No</option>
                                <option value="1" @if($TimesBanckHoliday->fixed == 1 ) Selected @endif>Yes</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" class="form-control" name="date"  id="date" value="{{ $TimesBanckHoliday->date }}">
                          </div>
                        </div>
                        <div class="card-footer">
                          <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                        </div>
                      </form>
                    </x-adminlte-modal>
                  </td>
                </tr>
                @empty
                  <x-EmptyDataLine col="4" text="No lines found ..."  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>Fixed</th>
                  <th>date</th>
                  <th>label</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <!-- /.card secondary -->
        </div>
        <div class="col-md-6 card-secondary">
            <div class="card-header">
              <h3 class="card-title">New Banck Holiday</h3>
            </div>
            <form  method="POST" action="{{ route('times.banckholiday.create') }}" class="form-horizontal">
              @csrf
              
              <div class="form-group">
                <label for="label">Label</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
                </div>
              </div>
              <div class="form-group">
                <label for="fixed">Fixed</label>
                <select class="form-control" name="fixed" id="fixed">
                    <option value="2">No</option>
                    <option value="1">Yes</option>
                </select>
              </div>
              <div class="form-group">
                <label for="date">Date</label>
                <input type="date" class="form-control" name="date"  id="date" >
              </div>
              <div class="card-footer">
                <div class="offset-sm-2 col-sm-10">
                  <button type="submit" class="btn btn-danger">Submit New</button>
                </div>
              </div>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="ImproductTime">
      <div class="row">
        <div class="col-md-6 card-primary">
          <div class="card-header">
              <h3 class="card-title">Improduct time list</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Desciption</th>
                  <th>Machine statu</th>
                  <th>Resource required</th>
                  <th>Mask time</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($TimesImproductTimes as $TimesImproductTime)
                <tr>
                  <td>{{ $TimesImproductTime->label }}</td>
                  <td>{{ $TimesImproductTime->MachineEvent['label'] }}</td>
                  <td>
                    @if($TimesImproductTime->resources_required  == 1)Yes @endif
                    @if($TimesImproductTime->resources_required  == 2)No @endif
                  </td>
                  <td>
                    @if($TimesImproductTime->mask_time  == 1)Yes @endif
                    @if($TimesImproductTime->mask_time  == 2)No @endif
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
                            <label for="label">Label</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $TimesImproductTime->label }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="times_machine_events_id">Machine status</label>
                            <select class="form-control" name="times_machine_events_id" id="times_machine_events_id">
                                @foreach ($TimesMachineEventsSelect as $item)
                                <option value="{{ $item->id }}" @if($TimesImproductTime->times_machine_events_id == $item->id ) Selected @endif>{{ $item->label }}</option>
                                @endforeach
                              </select>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="resources_required">Ressource required</label>
                            <select class="form-control" name="resources_required" id="resources_required">
                                <option value="2" @if($TimesImproductTime->resources_required == 2 ) Selected @endif>No</option>
                                <option value="1" @if($TimesImproductTime->resources_required == 1 ) Selected @endif>Yes</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="mask_time">Mask time</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                              </div>
                              <select class="form-control" name="mask_time" id="mask_time">
                                  <option value="2" @if($TimesImproductTime->mask_time == 2 ) Selected @endif>No</option>
                                  <option value="1" @if($TimesImproductTime->mask_time == 1 ) Selected @endif>Yes</option>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="card-footer">
                          <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                        </div>
                      </form>
                    </x-adminlte-modal>
                  </td>
                </tr>
                @empty
                  <x-EmptyDataLine col="5" text="No lines found ..."  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>Desciption</th>
                  <th>Machine statu</th>
                  <th>Resource required</th>
                  <th>Mask time</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <!-- /.card secondary -->
        </div>
        <div class="col-md-6 card-secondary">
          <div class="card-header">
            <h3 class="card-title">New Improduct time</h3>
          </div>
          <form  method="POST" action="{{ route('times.improducttime.create') }}" class="form-horizontal">
            @csrf
            <div class="form-group">
              <label for="label">Label</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control" name="label"  id="label" placeholder="Label" >
              </div>
            </div>
            <div class="form-group">
              <label for="times_machine_events_id">Machine status</label>
              <select class="form-control" name="times_machine_events_id" id="times_machine_events_id">
                  @foreach ($TimesMachineEventsSelect as $item)
                  <option value="{{ $item->id }}">{{ $item->label }}</option>
                  @endforeach
                </select>
              </select>
            </div>
            <div class="form-group">
              <label for="resources_required">Ressource required</label>
              <select class="form-control" name="resources_required" id="resources_required">
                  <option value="2">No</option>
                  <option value="1">Yes</option>
              </select>
            </div>
            <div class="form-group">
              <label for="mask_time">Mask time</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                </div>
                <select class="form-control" name="mask_time" id="mask_time">
                    <option value="2">No</option>
                    <option value="1">Yes</option>
                </select>
              </div>
            </div>
            <div class="card-footer">
              <div class="offset-sm-2 col-sm-10">
                <button type="submit" class="btn btn-danger">Submit New</button>
              </div>
            </div>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="MachineEvent">
      <div class="row">
        <div class="col-md-6 card-primary">
          <div class="card-header">
              <h3 class="card-title">Machine events list</h3>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>External ID</th>
                  <th>Order</th>
                  <th>Desciption</th>
                  <th>Mask time</th>
                  <th>Color</th>
                  <th>Statu</th>
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
                    @if($TimesMachineEvent->mask_time  == 1)Yes @endif
                    @if($TimesMachineEvent->mask_time  == 2)No @endif
                  </td>
                  <td><input type="color" class="form-control"  name="color" id="color" value="{{ $TimesMachineEvent->color }}"></td>
                  <td>
                    @if($TimesMachineEvent->etat  == 1)Stop @endif
                    @if($TimesMachineEvent->etat  == 2)Setup @endif
                    @if($TimesMachineEvent->etat  == 3)Run @endif
                    @if($TimesMachineEvent->etat  == 4)Off @endif
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
                            <label for="ordre">Sort order:</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                              </div>
                              <input type="number" class="form-control" name="ordre" id="ordre" placeholder="10" value="{{ $TimesMachineEvent->ordre }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="label">Label</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                              </div>
                              <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $TimesMachineEvent->label }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="mask_time">Mask time</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                              </div>
                              <select class="form-control" name="mask_time" id="mask_time">
                                  <option value="2"  @if($TimesMachineEvent->mask_time == 2 ) Selected @endif>No</option>
                                  <option value="1"  @if($TimesMachineEvent->mask_time == 1 ) Selected @endif>Yes</option>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label for="color">Color</label>
                            <input type="color" class="form-control"  name="color" id="color" value="{{ $TimesMachineEvent->color }}">
                          </div>
                          <div class="form-group">
                            <label for="etat">Status</label>
                            <select class="form-control" name="etat" id="etat">
                                <option value="1" @if($TimesMachineEvent->etat == 1 ) Selected @endif>Stop</option>
                                <option value="2" @if($TimesMachineEvent->etat == 2 ) Selected @endif>Setup</option>
                                <option value="3" @if($TimesMachineEvent->etat == 3 ) Selected @endif>Run</option>
                                <option value="4" @if($TimesMachineEvent->etat == 4 ) Selected @endif>Off</option>
                            </select>
                          </div>
                        </div>
                        <div class="card-footer">
                          <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                        </div>
                      </form>
                    </x-adminlte-modal>
                  </td>
                </tr>
                @empty
                  <x-EmptyDataLine col="7" text="No lines found ..."  />
                @endforelse
              </tbody>
              <tfoot>
                <tr>
                  <th>External ID</th>
                  <th>Order</th>
                  <th>Desciption</th>
                  <th>Mask time</th>
                  <th>Color</th>
                  <th>Statu</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <!-- /.card secondary -->
        </div>
        <div class="col-md-6 card-secondary">
          <div class="card-header">
            <h3 class="card-title">New machine event type</h3>
          </div>
          <form  method="POST" action="{{ route('times.machineevent.create') }}" class="form-horizontal">
            @csrf
            <div class="form-group">
              <label for="code">External ID</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                <input type="text" class="form-control" name="code" id="code" placeholder="External ID">
              </div>
            </div>
            <div class="form-group">
              <label for="ordre">Sort order:</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                </div>
                <input type="number" class="form-control" name="ordre" id="ordre" placeholder="10">
              </div>
            </div>
            <div class="form-group">
              <label for="label">Label</label>
              <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
              </div>
            </div>
            <div class="form-group">
              <label for="mask_time">Mask time</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                </div>
                <select class="form-control" name="mask_time" id="mask_time">
                    <option value="2">No</option>
                    <option value="1">Yes</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="color">Color</label>
              <input type="color" class="form-control"  name="color" id="color" >
            </div>
            <div class="form-group">
              <label for="etat">Status</label>
              <select class="form-control" name="etat" id="etat">
                  <option value="1">Stop</option>
                  <option value="2">Setup</option>
                  <option value="3">Run</option>
                  <option value="4">Off</option>
              </select>
            </div>
            <div class="card-footer">
              <div class="offset-sm-2 col-sm-10">
                <button type="submit" class="btn btn-danger">Submit New</button>
              </div>
            </div>
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