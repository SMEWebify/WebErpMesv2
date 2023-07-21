@extends('adminlte::page')

@section('title', 'Human resources')

@section('content_header')
    <h1>Human resources</h1>
@stop

@section('content')
@include('include.alert-result')
<div class="card">
    <!-- /.card-header -->
    <div class="card-body">
        <div class="table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-mail</th>
                        <th>employment statu</th>
                        <th>Job Title</th>
                        <th>Gender</th>
                        <th>Born date</th>
                        <th>Statu</th>
                        <th></th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Users as $User)
                    <tr>
                        <td>{{ $User->name }}</td>
                        <td>{{ $User->email }}</td>
                        <td>
                            @if(1 == $User->employment_status )   <span class="badge badge-danger">Undefined</span>@endif
                            @if(2 == $User->employment_status )  <span class="badge badge-success">worker</span>@endif
                            @if(3 == $User->employment_status )  <span class="badge badge-warning">Employee</span>@endif
                            @if(4 == $User->employment_status )  <span class="badge badge-info">Self-employed</span>@endif
                        </td>
                        <td>{{ $User->job_title ?? 'Undefined'}}</td>
                        <td>
                            @if(1 == $User->gender ) Male 
                            @elseif(2 == $User->gender ) Female
                            @elseif(3 == $User->gender ) Other 
                            @else Undefined
                            @endif
                        </td>
                        <td>{{ $User->born_date ?? 'Undefined' }}</td>
                        <td>
                            @if(1 == $User->statu )  <span class="badge badge-success">Active</span>@endif
                            @if(2 == $User->statu )  <span class="badge badge-danger">Inactive</span>@endif
                        </td>
                        <td class=" py-0 align-middle">
                            <!-- Button Modal -->
                            <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#User{{ $User->id }}">
                                <i class="fa fa-lg fa-fw  fa-edit"></i>
                            </button>
                            <!-- Modal {{ $User->id }} -->
                            <x-adminlte-modal id="User{{ $User->id }}" title="Update {{ $User->name }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
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
                                        <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                                    </div>
                                </form>
                            </x-adminlte-modal>
                        </td>
                        <td>{{ $User->GetPrettyCreatedAttribute() }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th>Name</th>
                    <th>E-mail</th>
                    <th>employment statu</th>
                    <th>Job Title</th>
                    <th>Gender</th>
                    <th>Born date</th>
                    <th>Statu</th>
                    <th></th>
                    <th>Created</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        <div class="row">
            <div class="col-5">
                {{ $Users->links() }}
            </div>
        </div>
        <!-- /.row -->
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('plugins.BootstrapSwitch', true)

@section('css')
@stop

@section('js')
@stop