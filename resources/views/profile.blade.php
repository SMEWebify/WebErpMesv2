
@extends('adminlte::page')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile</h1>
@stop

@section('content')

<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#Profil" data-toggle="tab">Profil setting</a></li>
            <li class="nav-item"><a class="nav-link" href="#History" data-toggle="tab">Notification history</a></li> 
            <li class="nav-item"><a class="nav-link" href="#LeaveRequest" data-toggle="tab">Leave request</a></li> 
        </ul>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="Profil">
                <div class="row">
                    @livewire('user-profile')

                    <div class="col-md-3">
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Information</h3>
                            </div>
                            <div class="card-body">
                                Account created at : {{ $UserProfil->GetPrettyCreatedAttribute() }}
                            </div>
                            <div class="card-body">
                                Employment status : {{ $UserProfil->employment_status}}
                            </div>
                            <div class="card-body">
                                Job title : {{ $UserProfil->job_title}}
                            </div>
                            <div class="card-body">
                                Pay grade : {{ $UserProfil->pay_grade}}
                            </div>
                            <div class="card-body">
                                Work station id : {{ $UserProfil->work_station_id}}
                            </div>
                            <div class="card-body">
                                Joined_date : {{ $UserProfil->joined_date}}
                            </div>
                            <div class="card-body">
                                Confirmation date : {{ $UserProfil->confirmation_date}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="History">
                @include('include.alert-result')
                <div class="row">
                    @livewire('notification-line')
                    <div class="col-md-3">
                        <form method="POST" action="{{ route('notifications.setting') }}" >
                            @csrf
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">Notification choice</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New companie</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->companies_notification == 1)  
                                                <x-adminlte-input-switch name="companies_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal"  checked />
                                            @else
                                                <x-adminlte-input-switch name="companies_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal"  />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New user</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->users_notification == 1)  
                                            <x-adminlte-input-switch name="users_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="users_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New quote</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->quotes_notification == 1)  
                                            <x-adminlte-input-switch name="quotes_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="quotes_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">New order</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->orders_notification == 1)  
                                            <x-adminlte-input-switch name="orders_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="orders_notification" data-on-text="YES" data-off-text="NO" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="success" icon="fas fa-lg fa-save"/>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane active" id="LeaveRequest">
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
                                    <td>
                                        @if($TimesAbsence->statu  == 1)To validate @endif
                                        @if($TimesAbsence->statu  == 2)Validate @endif
                                        @if($TimesAbsence->statu  == 3)Unvalidate @endif
                                    </td>
                                    <td>{{ $TimesAbsence->start_date }}</td>
                                    <td>{{ $TimesAbsence->end_date }}</td>
                                    <td class=" py-0 align-middle">
                                    @if($TimesAbsence->statu  == 1)
                                    <!-- Button Modal -->
                                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#TimesAbsence{{ $TimesAbsence->id }}">
                                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                                    </button>
                                    <!-- Modal {{ $TimesAbsence->id }} -->
                                    <x-adminlte-modal id="TimesAbsence{{ $TimesAbsence->id }}" title="Update {{ $TimesAbsence->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                        <form method="POST" action="{{ route('times.absence.update', ['id' => $TimesAbsence->id]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">
                                                <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
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
                                    @endif
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
                            <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
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
        </div>
    </div>
</div>
<!-- /.card -->
@stop

@section('plugins.BootstrapSwitch', true)


@section('css')
@stop

@section('js')
@stop