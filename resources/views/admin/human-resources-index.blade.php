@extends('adminlte::page')

@section('title', __('general_content.human_resources_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.human_resources_trans_key') }}</h1>
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
                        <th></th>
                        <th>{{ __('general_content.name_trans_key') }}</th>
                        <th>{{ __('general_content.email_trans_key') }}</th>
                        <th>{{ __('general_content.employment_statu_trans_key') }}</th>
                        <th>{{ __('general_content.job_title_trans_key') }}</th>
                        <th>{{ __('general_content.role_trans_key') }}</th>
                        <th>{{ __('general_content.gender_trans_key') }}</th>
                        <th>{{ __('general_content.born_date_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th></th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($Users as $User)
                    <tr>
                        <td>
                            @if(Cache::has('user-is-online-' . $User->id))
                                <span class="badge badge-success">{{__('general_content.online_trans_key') }}</span>
                            @else
                                <span class="badge badge-secondary">{{__('general_content.offline_trans_key') }}</span>
                            @endif 
                        </td>
                        <td>
                            {{ $User->name }}</td>
                        <td>{{ $User->email }}</td>
                        <td>
                            @if(1 == $User->employment_status )   <span class="badge badge-danger">{{__('general_content.undefined_trans_key') }}</span>@endif
                            @if(2 == $User->employment_status )  <span class="badge badge-success">{{__('general_content.worker_trans_key') }}</span>@endif
                            @if(3 == $User->employment_status )  <span class="badge badge-warning">{{__('general_content.employee_trans_key') }}</span>@endif
                            @if(4 == $User->employment_status )  <span class="badge badge-info">{{__('general_content.self_employed_trans_key') }}</span>@endif
                        </td>
                        <td>{{ $User->job_title ?? __('general_content.undefined_trans_key')}}</td>
                        <td>
                            @if(!empty($User->getRoleNames()))
                            @foreach($User->getRoleNames() as $v)
                                <label class="badge badge-success">{{ $v }}</label>
                            @endforeach
                            @endif
                        </td>
                        <td>
                            @if(1 == $User->gender ) {{__('general_content.male_trans_key') }} 
                            @elseif(2 == $User->gender ) {{__('general_content.female_trans_key') }}
                            @elseif(3 == $User->gender ) {{__('general_content.other_trans_key') }} 
                            @else {{__('general_content.undefined_trans_key') }}
                            @endif
                        </td>
                        <td>{{ $User->born_date ?? __('general_content.undefined_trans_key') }}</td>
                        <td>
                            @if(1 == $User->statu )  <span class="badge badge-success">{{__('general_content.active_trans_key') }}</span>@endif
                            @if(2 == $User->statu )  <span class="badge badge-danger">{{__('general_content.inactive_trans_key') }}</span>@endif
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
                        <th></th>
                        <th>{{ __('general_content.name_trans_key') }}</th>
                        <th>{{ __('general_content.email_trans_key') }}</th>
                        <th>{{ __('general_content.employment_statu_trans_key') }}</th>
                        <th>{{ __('general_content.job_title_trans_key') }}</th>
                        <th>{{ __('general_content.role_trans_key') }}</th>
                        <th>{{ __('general_content.gender_trans_key') }}</th>
                        <th>{{ __('general_content.born_date_trans_key') }}</th>
                        <th>{{__('general_content.status_trans_key') }}</th>
                        <th></th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
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