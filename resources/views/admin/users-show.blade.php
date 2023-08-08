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
                <p class="text-muted text-center">{{ $User->job_title }}</p>
                <p class="text-muted text-center">{{ $User->email }}</p>
                <ul class="list-group list-group-unbordered mb-3">
                <li class="list-group-item"><b>Quotes</b> <a class="float-right">{{ $User->quotes_count }}</a></li>
                <li class="list-group-item"><b>Orders</b> <a class="float-right">{{ $User->orders_count }}</a></li>
                <li class="list-group-item"><b>NC</b> <a class="float-right">{{ $User->quality_non_conformities_count }}</a></li>
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
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">HR information</a></li>
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