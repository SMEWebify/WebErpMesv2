
@extends('adminlte::page')

@section('title', __('general_content.profile_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.profile_trans_key') }}</h1>
@stop

@section('content')

<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#Profil" data-toggle="tab">{{ __('general_content.profil_setting_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#History" data-toggle="tab">{{ __('general_content.notification_history_trans_key') }}</a></li> 
            <li class="nav-item"><a class="nav-link" href="#LeaveRequest" data-toggle="tab">{{ __('general_content.leave_request_trans_key') }}</a></li> 
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
                                <h3 class="card-title">{{ __('general_content.information_trans_key') }}</h3>
                            </div>
                            <div class="card-body">
                                {{ __('general_content.created_at_trans_key') }} : {{ $UserProfil->GetPrettyCreatedAttribute() }}
                            </div>
                            <div class="card-body">
                                {{ __('general_content.employment_statu_trans_key') }} : {{ $UserProfil->employment_status}}
                            </div>
                            <div class="card-body">
                                {{ __('general_content.job_title_trans_key') }} : {{ $UserProfil->job_title}}
                            </div>
                            <div class="card-body">
                                {{ __('general_content.pay_grade_trans_key') }} : {{ $UserProfil->pay_grade}}
                            </div>
                            <div class="card-body">
                                {{ __('general_content.work_station_id_trans_key') }} : {{ $UserProfil->work_station_id}}
                            </div>
                            <div class="card-body">
                                {{ __('general_content.joined_date_trans_key') }} : {{ $UserProfil->joined_date}}
                            </div>
                            <div class="card-body">
                                {{ __('general_content.confirmation_date_trans_key') }} : {{ $UserProfil->confirmation_date}}
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
                                    <h3 class="card-title">{{ __('general_content.notification_choice_trans_key') }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.new_companie_trans_key') }}</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->companies_notification == 1)  
                                                <x-adminlte-input-switch name="companies_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"  checked />
                                            @else
                                                <x-adminlte-input-switch name="companies_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"  />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.new_user_trans_key') }}</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->users_notification == 1)  
                                            <x-adminlte-input-switch name="users_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="users_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.new_quote_trans_key') }}</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->quotes_notification == 1)  
                                            <x-adminlte-input-switch name="quotes_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="quotes_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.new_order_trans_key') }}</label></div>
                                        <div class="col-8">
                                            @if($UserProfil->orders_notification == 1)  
                                            <x-adminlte-input-switch name="orders_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                                            @else
                                            <x-adminlte-input-switch name="orders_notification" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="LeaveRequest">
                <div class="row">
                    <div class="col-md-6 card-primary">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('general_content.leave_request_trans_key') }}</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
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
                                    @endif
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
                    <!-- /.card secondary -->
                    </div>
                    <div class="col-md-6 card-secondary">
                        <div class="card-header">
                        <h3 class="card-title">{{ __('general_content.new_absence_request_trans_key') }}</h3>
                        </div>
                        <form  method="POST" action="{{ route('times.absence.create') }}" class="form-horizontal">
                        @csrf
                        <div class="form-group">
                            <label for="absence_type">{{ __('general_content.absence_type_trans_key') }}</label>
                            <select class="form-control" name="absence_type" id="absence_type">
                                <option value="1">{{ __('general_content.full_day_absence_trans_key') }}</option>
                                <option value="2">{{ __('general_content.1_half_day_absence_trans_key') }}</option>
                                <option value="3">{{ __('general_content.2_half_day_absence_trans_key') }}</option>
                                <option value="4">{{ __('general_content.absence_in_hours_trans_key') }}</option>
                            </select>
                            <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
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
                        <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
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