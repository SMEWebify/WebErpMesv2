@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>Setting user for {{ $User->name }}</h1>
@stop



@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                <img class="profile-user-img img-fluid img-circle" src="{{ $User->adminlte_image() }}" alt="User profile picture">
                </div>
                <h3 class="profile-username text-center">{{ $User->name }}</h3>
                <p class="text-muted text-center">
                    @if(!empty($User->getRoleNames()))
                    @foreach($User->getRoleNames() as $v)
                        <label class="badge badge-success">{{ $v }}</label>
                    @endforeach
                    @endif
                </p>
                <p class="text-muted text-center">{{ $User->email }}</p>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item"><b>Leads</b> <a class="float-right">{{ $User->getLeadsCountAttribute() }}</a></li>
                    <li class="list-group-item"><b>Quotes</b> <a class="float-right">{{ $User->getQuotesCountAttribute() }}</a></li>
                    <li class="list-group-item"><b>Orders</b> <a class="float-right">{{ $User->getOrdersCountAttribute() }}</a></li>
                    <li class="list-group-item"><b>NC</b> <a class="float-right">{{ $User->getNcCountAttribute() }}</a></li>
                </ul>
            </div>
        </div>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">About {{ $User->name }}</h3>
            </div>
            <div class="card-body">
                @if($User->private_email)
                <strong><i class="fas fa-envelope  mr-1"></i>Personnal E-mail</strong>
                <p class="text-muted">{{ $User->private_email }}</p>
                <hr>
                @endif
                @if($User->personnal_phone_number)
                <strong><i class="fas fa-phone  mr-1"></i>Personnal phone number</strong>
                <p class="text-muted">{{ $User->personnal_phone_number }}</p>
                <hr>
                @endif
                @if($User->born_date)
                <strong><i class="far fa-calendar-alt  mr-1"></i>Born date</strong>
                <p class="text-muted">{{ $User->born_date }}</p>
                <hr>
                @endif
                @if($User->nationality)
                <strong><i class="fas fa-flag mr-1"></i>Nationality</strong>
                <p class="text-muted">{{ $User->nationality }}</p>
                <hr>
                @endif
                <strong><i class="fas fa-map-marker-alt mr-1"></i>Location</strong>
                <p class="text-muted">{{ $User->address1 }}</p>
                <p class="text-muted">{{ $User->address2 }}</p>
                <p class="text-muted">{{ $User->postal_code }} {{ $User->city }} </p>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        
        @include('include.alert-result')
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">HR information</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contract" data-toggle="tab">Contract</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="activity">
                        <form method="POST" action="{{ route('human.resources.update.user', ['id' => $User->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="job_title">Job title :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  name="job_title" id="job_title" placeholder="Job title" value="{{ $User->job_title }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="role">Role :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="role" id="role">
                                            @forelse ($Roles as $Role)
                                            <option value="{{ $Role->name }}" @if($User->getRoleNames() == $Role->name  ) Selected @endif>{{ $Role->name }}</option>
                                            @empty
                                            <option value="">No role, please add before</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="pay_grade">Pay grade :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control"  name="pay_grade" id="pay_grade" placeholder="Pay grade" value="{{ $User->pay_grade }}" min="0">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="work_station_id">Work station id :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  name="work_station_id" id="work_station_id" placeholder="Work station id" value="{{ $User->work_station_id }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="joined_date">Joined date :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control"   name="joined_date"  id="joined_date" value="{{ $User->joined_date }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="confirmation_date">Confirmation date :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control"   name="confirmation_date"  id="confirmation_date" value="{{ $User->confirmation_date }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="termination_date">Termination date :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control"   name="termination_date"  id="termination_date" value="{{ $User->termination_date }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="employment_status">Employment status</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="employment_status" id="employment_status">
                                            <option value="1" @if($User->employment_status == 1) Selected @endif>Undefined</option>
                                            <option value="2" @if($User->employment_status == 2) Selected @endif>worker</option>
                                            <option value="3" @if($User->employment_status == 3) Selected @endif>Employee</option>
                                            <option value="4" @if($User->employment_status == 4) Selected @endif>Self-employed</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="supervisor_id">Supervisor</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control" name="supervisor_id" id="supervisor_id">
                                            @foreach ($userSelect as $item)
                                            <option value="{{ $item->id }}" @if($User->supervisor_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="section_id">Section</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-industry"></i></span>
                                        </div>
                                        <select class="form-control" name="section_id" id="section_id">
                                            @forelse ($SectionsSelect as $item)
                                            <option value="{{ $item->id }}" @if($User->section_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                            @empty
                                            <option value="">No section, please add before</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="statu">Active</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="statu" id="statu">
                                            <option value="1" @if($User->statu == 1) Selected @endif>Active</option>
                                            <option value="2" @if($User->statu == 2) Selected @endif>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                            </div>
                            <div class="card-footer">
                                <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane" id="contract">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="table-responsive p-0">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Statu</th>
                                                <th>Type of contract</th>
                                                <th>Start date</th>
                                                <th>Weekly duration</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($UserEmploymentContracts as $UserEmploymentContract)
                                            <tr>
                                                <td>
                                                    @if($UserEmploymentContract->statu == 1) <span class="badge badge-warning">On trial</span> @endif
                                                    @if($UserEmploymentContract->statu == 2)<span class="badge badge-success">Asset</span> @endif
                                                    @if($UserEmploymentContract->statu == 3)<span class="badge badge-danger">Closed</span> @endif
                                                </td>
                                                <td>{{ $UserEmploymentContract->type_of_contract }}</td>
                                                <td>{{ $UserEmploymentContract->start_date }}</td>
                                                <td>{{ $UserEmploymentContract->weekly_duration }}</td>
                                                <td class="py-0 align-middle">
                                                    <!-- Button Modal -->
                                                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#UserEmploymentContract{{ $UserEmploymentContract->id }}">
                                                    <i class="fa fa-lg fa-fw  fa-edit"></i>
                                                    </button>
                                                    <!-- Modal {{ $UserEmploymentContract->id }} -->
                                                        <x-adminlte-modal id="UserEmploymentContract{{ $UserEmploymentContract->id }}" title="Update {{ $UserEmploymentContract->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                                        <form method="POST" action="{{ route('human.resources.update.contract', ['id' => $UserEmploymentContract->id]) }}" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="card-body">
                                                                <input type="hidden" name="user_id" id="user_id" value="{{ $User->id }}">
                                                                <div class="form-group">
                                                                    <label for="statu">Statu</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <select class="form-control" name="statu" id="statu">
                                                                            <option value="1" @if($UserEmploymentContract->statu == 1  ) Selected @endif>On trial</option>
                                                                            <option value="2" @if($UserEmploymentContract->statu == 2  ) Selected @endif>Asset</option>
                                                                            <option value="3" @if($UserEmploymentContract->statu == 3  ) Selected @endif>Closed</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="methods_section_id">Section concern:</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <select class="form-control" name="methods_section_id" id="methods_section_id">
                                                                            @forelse ($SectionsSelect as $item)
                                                                            <option value="{{ $item->id }}" @if($UserEmploymentContract->methods_section_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                                                            @empty
                                                                            <option value="">No section, please add before</option>
                                                                            @endforelse
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="signature_date">Signature date :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                                        </div>
                                                                        <input type="date" class="form-control"   name="signature_date"  id="signature_date" value="{{ $UserEmploymentContract->signature_date }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="type_of_contract">Type of contract :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <input type="text" class="form-control"  name="type_of_contract" id="type_of_contract" value="{{ $UserEmploymentContract->type_of_contract }}" placeholder="Type of contract">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="start_date">Start date :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                                        </div>
                                                                        <input type="date" class="form-control"   name="start_date"  id="start_date" value="{{ $UserEmploymentContract->start_date }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="duration_trial_period">Duration trial period :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-stopwatch"> Day(s)</i></span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="duration_trial_period" id="duration_trial_period" placeholder="Duration trial period" value="{{ $UserEmploymentContract->duration_trial_period }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="start_date">End date :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                                        </div>
                                                                        <input type="date" class="form-control"   name="end_date"  id="end_date" value="{{ $UserEmploymentContract->end_date }}" >
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="weekly_duration">Weekly duration :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-stopwatch"> Hour(s)</i></span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="weekly_duration" id="weekly_duration" placeholder="Weekly duration" value="{{ $UserEmploymentContract->weekly_duration }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="position">Position :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <input type="text" class="form-control"  name="position" id="position" placeholder="Position" value="{{ $UserEmploymentContract->position }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="coefficient">Coefficient :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <input type="text" class="form-control"  name="coefficient" id="coefficient" placeholder="Coefficient"  value="{{ $UserEmploymentContract->coefficient }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="hourly_gross_salary">Hourly gross salary :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="hourly_gross_salary" id="hourly_gross_salary" placeholder="Hourly gross salary"  value="{{ $UserEmploymentContract->hourly_gross_salary }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="minimum_monthly_salary">Minimum monthly salary :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="minimum_monthly_salary" id="minimum_monthly_salary" placeholder="Minimum monthly salary"  value="{{ $UserEmploymentContract->minimum_monthly_salary }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="annual_gross_salary">Annual gross salary :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="annual_gross_salary" id="annual_gross_salary" placeholder="Annual gross salary"  value="{{ $UserEmploymentContract->annual_gross_salary }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                            </div>
                                                            <div class="card-footer">
                                                                <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                                                            </div>
                                                        </form>
                                                    </x-adminlte-modal>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Statu</th>
                                                <th>Type of contract</th>
                                                <th>Start date</th>
                                                <th>Weekly duration</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <!-- /.row -->
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">New contract</h3>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('human.resources.create.contract', ['id' => $User->id]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">
                                                <input type="hidden" name="user_id" id="user_id" value="{{ $User->id }}">
                                                <div class="form-group">
                                                    <label for="statu">Statu</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                        </div>
                                                        <select class="form-control" name="statu" id="statu">
                                                            <option value="1">On trial</option>
                                                            <option value="2">Asset</option>
                                                            <option value="3">Closed</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="methods_section_id">Section concern:</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                        </div>
                                                        <select class="form-control" name="methods_section_id" id="methods_section_id">
                                                            @forelse ($SectionsSelect as $item)
                                                            <option value="{{ $item->id }}">{{ $item->label }}</option>
                                                            @empty
                                                            <option value="">No section, please add before</option>
                                                            @endforelse
                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="signature_date">Signature date :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                        </div>
                                                        <input type="date" class="form-control"   name="signature_date"  id="signature_date">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="type_of_contract">Type of contract :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"  name="type_of_contract" id="type_of_contract" placeholder="Type of contract">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="start_date">Start date :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                        </div>
                                                        <input type="date" class="form-control"   name="start_date"  id="start_date">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="duration_trial_period">Duration trial period :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-stopwatch"> Day(s)</i></span>
                                                        </div>
                                                        <input type="number" class="form-control"  name="duration_trial_period" id="duration_trial_period" placeholder="Duration trial period" step="1" min="0">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="start_date">End date :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                        </div>
                                                        <input type="date" class="form-control"   name="end_date"  id="end_date">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="weekly_duration">Weekly duration :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-stopwatch"> Hour(s)</i></span>
                                                        </div>
                                                        <input type="number" class="form-control"  name="weekly_duration" id="weekly_duration" placeholder="Weekly duration" step="1" min="0">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="position">Position :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"  name="position" id="position" placeholder="Position">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="coefficient">Coefficient :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control"  name="coefficient" id="coefficient" placeholder="Coefficient">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="hourly_gross_salary">Hourly gross salary :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                        </div>
                                                        <input type="number" class="form-control"  name="hourly_gross_salary" id="hourly_gross_salary" placeholder="Hourly gross salary" step="1" min="0">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="minimum_monthly_salary">Minimum monthly salary :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                        </div>
                                                        <input type="number" class="form-control"  name="minimum_monthly_salary" id="minimum_monthly_salary" placeholder="Minimum monthly salary" step="1" min="0">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                                <div class="form-group">
                                                    <label for="annual_gross_salary">Annual gross salary :</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                        </div>
                                                        <input type="number" class="form-control"  name="annual_gross_salary" id="annual_gross_salary" placeholder="Annual gross salary" step="1" min="0">
                                                    </div>
                                                </div>
                                                <!-- /.form-group -->
                                            </div>
                                            <div class="card-footer">
                                                <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
@stop

@section('js')
@stop