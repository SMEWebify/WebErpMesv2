@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Times setting</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @if(session('success'))
  <div class="alert alert-success">
      {{ session('success')}}
  </div>
  @endif

  @if($errors->count())
    <div class="alert alert-danger">
      <ul>
      @foreach ( $errors->all() as $message)
       <li> {{ $message }}</li>
      @endforeach
      </ul>
    </div>
  @endif
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Absence" data-toggle="tab">Absence</a></li>
      <li class="nav-item"><a class="nav-link" href="#BanckHoliday" data-toggle="tab">Banck holiday</a></li>
      <li class="nav-item"><a class="nav-link" href="#ImproductTime" data-toggle="tab">Improduct time</a></li>
      <li class="nav-item"><a class="nav-link" href="#MachineEvent" data-toggle="tab">Machine event</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Absence">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Absence Request</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
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
                        <td>{{ $TimesAbsence->STATU }}</td>
                        <td>{{ $TimesAbsence->START_DATE }}</td>
                        <td>{{ $TimesAbsence->END_DATE }}</td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                      </tr>
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
                      <label for="START_DATE">Start date</label>
                      <input type="date" class="form-control" name="START_DATE"  id="START_DATE" >
                    </div>
                    <div class="form-group">
                      <label for="END_DATE">End date</label>
                      <input type="date" class="form-control" name="END_DATE"  id="END_DATE" >
                    </div>
                    <div class="card-footer">
                      <div class="offset-sm-2 col-sm-10">
                        <button type="submit" class="btn btn-danger">Submit New</button>
                      </div>
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
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane " id="BanckHoliday">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Banck Holidays list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Fixed</th>
                        <th>DATE</th>
                        <th>LABEL</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($TimesBanckHolidays as $TimesBanckHoliday)
                      <tr>
                        <td>
                          @if($TimesBanckHoliday->FIXED  == 1)Yes @endif
                          @if($TimesBanckHoliday->FIXED  == 2)No @endif
                        </td>
                        <td>{{ $TimesBanckHoliday->DATE }}</td>
                        <td>{{ $TimesBanckHoliday->LABEL }}</td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Fixed</th>
                        <th>DATE</th>
                        <th>LABEL</th>
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
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="FIXED">Fixed</label>
                      <select class="form-control" name="FIXED" id="FIXED">
                          <option value="2">No</option>
                          <option value="1">Yes</option>
                      </select>
                     </div>
                     <div class="form-group">
                      <label for="DATE">Date</label>
                      <input type="date" class="form-control" name="DATE"  id="DATE" >
                    </div>
                    <div class="card-footer">
                      <div class="offset-sm-2 col-sm-10">
                        <button type="submit" class="btn btn-danger">Submit New</button>
                      </div>
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
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="ImproductTime">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Improduct time list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
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
                        <td>{{ $TimesImproductTime->LABEL }}</td>
                        <td>{{ $TimesImproductTime->MachineEvent['LABEL'] }}</td>
                        <td>
                          @if($TimesImproductTime->RESOURCE_REQUIRED  == 1)Yes @endif
                          @if($TimesImproductTime->RESOURCE_REQUIRED  == 2)No @endif
                        </td>
                        <td>
                           @if($TimesImproductTime->MASK_TIME  == 1)Yes @endif
                           @if($TimesImproductTime->MASK_TIME  == 2)No @endif
                        </td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td>
                      </tr>
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
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                       <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="MACHINE_STATUS">Machine status</label>
                      <select class="form-control" name="MACHINE_STATUS" id="MACHINE_STATUS">
                          @foreach ($TimesMachineEventsSelect as $item)
                          <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                          @endforeach
                        </select>
                      </select>
                     </div>
                    <div class="form-group">
                      <label for="RESOURCE_REQUIRED">Ressource required</label>
                      <select class="form-control" name="RESOURCE_REQUIRED" id="RESOURCE_REQUIRED">
                          <option value="2">No</option>
                          <option value="1">Yes</option>
                      </select>
                     </div>
                    <div class="form-group">
                      <label for="MASK_TIME">Mask time</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                        </div>
                        <select class="form-control" name="MASK_TIME" id="MASK_TIME">
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
                <!-- /.card secondary -->
                </div>
              <!-- /.row -->
              </div>
            <!-- /.card body -->
            </div>
          <!-- /.card primary -->
          </div>
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="MachineEvent">
        <div class="card card-primary">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Machine events list</h3>
                </div>
                <div class="card-body p-0">
                  <table class="table">
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
                        <td>{{ $TimesMachineEvent->CODE }}</td>
                        <td>{{ $TimesMachineEvent->ORDRE }}</td>
                        <td>{{ $TimesMachineEvent->LABEL }}</td>
                        <td>
                          @if($TimesMachineEvent->MASK_TIME  == 1)Yes @endif
                          @if($TimesMachineEvent->MASK_TIME  == 2)No @endif
                        </td>
                        <td>{{ $TimesMachineEvent->COLOR }}</td>
                        <td>
                          @if($TimesMachineEvent->ETAT  == 1)Stop @endif
                          @if($TimesMachineEvent->ETAT  == 2)Setup @endif
                          @if($TimesMachineEvent->ETAT  == 3)Run @endif
                          @if($TimesMachineEvent->ETAT  == 4)Off @endif
                        </td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td>
                        <td></td>
                      </tr>
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
                      <label for="CODE">External ID</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                        </div>
                        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="ORDRE">Sort order:</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                        </div>
                       <input type="number" class="form-control" name="ORDRE" id="ORDRE" placeholder="10">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="LABEL">Label</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                        </div>
                        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="MASK_TIME">Mask time</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-times"></i></span>
                        </div>
                        <select class="form-control" name="MASK_TIME" id="MASK_TIME">
                            <option value="2">No</option>
                            <option value="1">Yes</option>
                        </select>
                      </div>
                     </div>
                     <div class="form-group">
                      <label for="COLOR">Color</label>
                      <input type="color" class="form-control"  name="COLOR" id="COLOR" >
                    </div>
                    <div class="form-group">
                      <label for="ETAT">Status</label>
                      <select class="form-control" name="ETAT" id="ETAT">
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
                <!-- /.card secondary -->
                </div>
              <!-- /.row -->
              </div>
            <!-- /.card body -->
            </div>
          <!-- /.card primary -->
          </div>
      </div>
      <!-- /.tab-pane -->

    <!-- /.tab-content -->
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
</div>

@stop
                  
 @section('css')
    
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop