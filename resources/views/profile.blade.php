
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
            <li class="nav-item"><a class="nav-link" href="#ExpenseReport" data-toggle="tab">{{ __('general_content.expense_report_trans_key') }}</a></li> 
        </ul>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="Profil">
                <div class="row">
                    @livewire('user-profile')
                    <div class="col-md-3">
                        <x-adminlte-card title="{{ __('general_content.information_trans_key') }}" theme="secondary" maximizable>
                            <div class="card-body">
                                <strong> {{ __('general_content.created_at_trans_key') }} :</strong> {{ $UserProfil->GetPrettyCreatedAttribute() }}
                            </div>
                            <div class="card-body">
                                <strong>{{ __('general_content.employment_statu_trans_key') }} :</strong> {{ $UserProfil->employment_status}}
                            </div>
                            <div class="card-body">
                                <strong>{{ __('general_content.job_title_trans_key') }} :</strong> {{ $UserProfil->job_title}}
                            </div>
                            <div class="card-body">
                                <strong>{{ __('general_content.pay_grade_trans_key') }} :</strong> {{ $UserProfil->pay_grade}}
                            </div>
                            <div class="card-body">
                                <strong>{{ __('general_content.work_station_id_trans_key') }} :</strong> {{ $UserProfil->work_station_id}}
                            </div>
                            <div class="card-body">
                                <strong>{{ __('general_content.joined_date_trans_key') }} :</strong> {{ $UserProfil->joined_date}}
                            </div>
                            <div class="card-body">
                                <strong>{{ __('general_content.confirmation_date_trans_key') }} : </strong>{{ $UserProfil->confirmation_date}}
                            </div>
                        </x-adminlte-card>
                        <x-adminlte-card title="{{ __('general_content.information_trans_key') }} [FR] for support" theme="purple" maximizable>
                            
                            <div class="card-body"><strong>Adresse IP :</strong> {{ $data['ipAddress'] }}</div>
                            
                            <div class="card-body"><strong>Navigateur :</strong> {{ $data['browser'] }}</div>
                            
                            <div class="card-body"><strong>Version navigateur :</strong> {{ $data['browserVersion'] }}</div>
                            
                            <div class="card-body"><strong>Système d'exploitation :</strong> {{ $data['platform'] }}</div>
                            
                            <div class="card-body"><strong>Version Système d'exploitation :</strong> {{ $data['platformVersion'] }}</div>
                            
                            <div class="card-body"><strong>Langue du Navigateur :</strong> {{ $data['language'] }}</div>
                            
                            <div class="card-body"><strong>Device :</strong> {{ $data['device'] }}</div>
                            
                            <div class="card-body"><strong>Desktop :</strong> -{{ $data['isDesktop'] }}-</div>
                            
                            <div class="card-body"><strong>Is Phone :</strong> -{{ $data['isPhone'] }}-</div>
                        </x-adminlte-card>
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
                            <x-adminlte-card title="{{ __('general_content.notification_choice_trans_key') }}" theme="teal" maximizable>
                                <div class="row">
                                    <div class="col-4 text-right"><label for="companies_notification" class="col-form-label">{{ __('general_content.new_companie_trans_key') }}</label></div>
                                    <div class="col-8">
                                        <x-adminlte-input-switch name="companies_notification" 
                                                                data-on-text="{{ __('general_content.yes_trans_key') }}" 
                                                                data-off-text="{{ __('general_content.no_trans_key') }}"
                                                                data-on-color="teal" 
                                                                data-off-color="danger" 
                                                                is-checked="{{ $UserProfil->companies_notification }}" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-right"><label for="users_notification" class="col-form-label">{{ __('general_content.new_user_trans_key') }}</label></div>
                                    <div class="col-8">
                                        <x-adminlte-input-switch name="users_notification" 
                                                                data-on-text="{{ __('general_content.yes_trans_key') }}" 
                                                                data-off-text="{{ __('general_content.no_trans_key') }}"
                                                                data-on-color="teal" 
                                                                data-off-color="danger" 
                                                                is-checked="{{ $UserProfil->users_notification }}" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-right"><label for="quotes_notification"  class="col-form-label">{{ __('general_content.new_quote_trans_key') }}</label></div>
                                    <div class="col-8">
                                        <x-adminlte-input-switch name="quotes_notification" 
                                                                data-on-text="{{ __('general_content.yes_trans_key') }}" 
                                                                data-off-text="{{ __('general_content.no_trans_key') }}"
                                                                data-on-color="teal" 
                                                                data-off-color="danger" 
                                                                is-checked="{{ $UserProfil->quotes_notification }}" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-right"><label for="orders_notification" class="col-form-label">{{ __('general_content.new_order_trans_key') }}</label></div>
                                    <div class="col-8">
                                        <x-adminlte-input-switch name="orders_notification" 
                                                                data-on-text="{{ __('general_content.yes_trans_key') }}" 
                                                                data-off-text="{{ __('general_content.no_trans_key') }}"
                                                                data-on-color="teal" 
                                                                data-off-color="danger" 
                                                                is-checked="{{ $UserProfil->orders_notification }}" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-right"><label for="non_conformity_notification" class="col-form-label">{{ __('general_content.new_non_conformitie_trans_key') }}</label></div>
                                    <div class="col-8">
                                        <x-adminlte-input-switch name="non_conformity_notification" 
                                            data-on-text="{{ __('general_content.yes_trans_key') }}" 
                                            data-off-text="{{ __('general_content.no_trans_key') }}"
                                            data-on-color="teal" 
                                            data-off-color="danger" 
                                            is-checked="{{ $UserProfil->non_conformity_notification }}" />
                                    </div>
                                </div>
                                <x-slot name="footerSlot">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                </x-slot>
                            </x-adminlte-card>
                        </form>
                    </div>
                </div>
                <div class="row mt-3">
                    @livewire('user-auto-email-reports')
                </div>
            </div>
            <div class="tab-pane" id="LeaveRequest">
                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-card title="{{ __('general_content.leave_request_trans_key') }}" theme="primary" maximizable>
                            <div class="table-responsive p-0">
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
                                        <x-button-text-edit :modalTarget="'TimesAbsence' . $TimesAbsence->id" />
                                        <!-- Modal {{ $TimesAbsence->id }} -->
                                        <x-adminlte-modal id="TimesAbsence{{ $TimesAbsence->id }}" title="Update {{ $TimesAbsence->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                            <form method="POST" action="{{ route('times.absence.update', ['id' => $TimesAbsence->id] ) }}" enctype="multipart/form-data">
                                                @csrf
                                                <div class="card-body">
                                                    <input type="hidden" name="user_id" id="user_id" value="{{ Auth::id() }}">
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
                        </x-adminlte-card>
                    </div>
                    <div class="col-md-6">
                        <form  method="POST" action="{{ route('times.absence.create') }}" class="form-horizontal">
                            <x-adminlte-card title="{{ __('general_content.new_absence_request_trans_key') }}" theme="secondary" maximizable>
                                @csrf
                                <div class="form-group">
                                    <label for="absence_type">{{ __('general_content.absence_type_trans_key') }}</label>
                                    <select class="form-control" name="absence_type" id="absence_type">
                                        <option value="1">{{ __('general_content.full_day_absence_trans_key') }}</option>
                                        <option value="2">{{ __('general_content.1_half_day_absence_trans_key') }}</option>
                                        <option value="3">{{ __('general_content.2_half_day_absence_trans_key') }}</option>
                                        <option value="4">{{ __('general_content.absence_in_hours_trans_key') }}</option>
                                    </select>
                                    <input type="hidden" name="user_id" id="user_id" value="{{ Auth::id() }}">
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
                <!-- /.row -->
                </div>
            </div>
            <div class="tab-pane" id="ExpenseReport">
                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-card title="{{ __('general_content.expense_report_trans_key') }}" theme="primary" maximizable>
                            <div class="table-responsive p-0">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>{{__('general_content.label_trans_key') }}</th>
                                        <th>{{__('general_content.status_trans_key') }}</th>
                                        <th>{{__('general_content.date_trans_key') }}</th>
                                        <th></th>
                                        <th>{{__('general_content.amount_trans_key') }}</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($ExpenseReports as $ExpenseReport)
                                    <tr>
                                        <td>{{ $ExpenseReport->label }}</td>
                                        <td>
                                            @if($ExpenseReport->status  == 1){{__('general_content.done_trans_key') }} @endif
                                            @if($ExpenseReport->status  == 2){{__('general_content.to_submit_trans_key') }} @endif
                                            @if($ExpenseReport->status  == 3){{__('general_content.submitted_trans_key') }} @endif
                                            @if($ExpenseReport->status  == 4){{__('general_content.returned_trans_key') }} @endif
                                            @if($ExpenseReport->status  == 5){{__('general_content.approved_trans_key') }} @endif
                                        </td>
                                        <td>{{ $ExpenseReport->date }}</td>
                                        <td>{{ $ExpenseReport->expenses()->count() }}</td>
                                        <td>{{ $ExpenseReport->getTotalAmountAttribute() }} {{ $Factory->curency }}</td>
                                        <td class=" py-0 align-middle">
                                            <div class="btn-group btn-group-sm">
                                                <x-ButtonTextView route="{{ route('human.resources.show.expense', ['id' => $ExpenseReport->id])}}" />
                                            </div>
                                            @if($ExpenseReport->status  == 1 || $ExpenseReport->status  == 2 || $ExpenseReport->status  == 4)
                                            <!-- Button Modal -->
                                            <div class="btn-group btn-group-sm">
                                                <x-ButtonTextEdit :modalTarget="'ExpenseReport' . $ExpenseReport->id" />
                                            </div>
                                            <!-- Modal {{ $ExpenseReport->id }} -->
                                            <x-adminlte-modal id="ExpenseReport{{ $ExpenseReport->id }}" title="Update {{ $ExpenseReport->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                                <form method="POST" action="{{ route('human.resources.update.expense.report', ['id' => $ExpenseReport->id]) }}" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="card-body">
                                                        <div class="form-group">
                                                            <label for="label">{{__('general_content.label_trans_key') }}</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                </div>
                                                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $ExpenseReport->label }}">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label for="status">{{ __('general_content.status_trans_key') }}</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                                </div>
                                                                <select class="form-control" name="status" id="status">
                                                                    <option value="1" @if($ExpenseReport->status == 1) Selected @endif>{{__('general_content.done_trans_key') }}</option>
                                                                    <option value="2" @if($ExpenseReport->status == 2) Selected @endif>{{__('general_content.to_submit_trans_key') }}</option>
                                                                    <option value="3" @if($ExpenseReport->status == 3) Selected @endif>{{__('general_content.submitted_trans_key') }}</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="date">{{ __('general_content.date_trans_key') }}</label>
                                                            <input type="date" class="form-control" name="date"  id="date" value="{{ $ExpenseReport->date }}">
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
                                            <th>{{__('general_content.label_trans_key') }}</th>
                                            <th>{{__('general_content.status_trans_key') }}</th>
                                            <th>{{__('general_content.date_trans_key') }}</th>
                                            <th></th>
                                            <th>{{__('general_content.amount_trans_key') }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </x-adminlte-card>
                    </div>
                    <div class="col-md-6">
                        <form  method="POST" action="{{ route('human.resources.create.expense.report') }}" class="form-horizontal" enctype="multipart/form-data">
                            <x-adminlte-card title="{{ __('general_content.new_expense_report_trans_key') }}" theme="secondary" maximizable>
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
                                    <label for="date">{{ __('general_content.date_trans_key') }}</label>
                                    <input type="date" class="form-control" name="date"  id="date" >
                                </div>
                                <x-slot name="footerSlot">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </x-slot>
                            </x-adminlte-card>
                        </form>
                    </div>
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
