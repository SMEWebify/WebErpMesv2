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
                        <td>
                            <x-ButtonTextView route="{{ route('human.resources.show.user', ['id' => $User->id])}}" />
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