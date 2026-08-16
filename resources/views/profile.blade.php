
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
            <li class="nav-item"><a class="nav-link" href="#MyDocuments" data-toggle="tab">{{ __('general_content.documents_trans_key') }}</a></li> 
        </ul>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        @include('include.alert-result')
        <div class="tab-content">
            <div class="tab-pane active" id="Profil">
                <div class="row">
                    <div id="user-profile-app" class="col-md-9"
                         data-initial="{{ json_encode($profileInitial) }}"
                         data-endpoints="{{ json_encode($profileEndpoints) }}"
                         data-trans="{{ json_encode([
                             'about_setup'           => __('general_content.about_setup_trans_key'),
                             'personnal_information' => __('general_content.personnal_information_trans_key'),
                             'name'                  => __('general_content.name_trans_key'),
                             'email'                 => __('general_content.email_trans_key'),
                             'personnal_phone'       => __('general_content.personnal_phone_trans_key'),
                             'personnal_email'       => __('general_content.personnal_email_trans_key'),
                             'born_date'             => __('general_content.born_date_trans_key'),
                             'nationality'           => __('general_content.nationality_trans_key'),
                             'gender'                => __('general_content.gender_trans_key'),
                             'select_gender'         => __('general_content.select_gender_trans_key'),
                             'male'                  => __('general_content.male_trans_key'),
                             'female'                => __('general_content.female_trans_key'),
                             'other'                 => __('general_content.other_trans_key'),
                             'marital_status'        => __('general_content.marital_status_trans_key'),
                             'select_marital_status' => __('general_content.select_marital_status_trans_key'),
                             'married'               => __('general_content.married_trans_key'),
                             'single'                => __('general_content.single_trans_key'),
                             'divorced'              => __('general_content.divorced_trans_key'),
                             'widowed'               => __('general_content.widowed_trans_key'),
                             'driving_license'       => __('general_content.driving_license_trans_key'),
                             'driving_license_exp_date' => __('general_content.driving_license_exp_date_trans_key'),
                             'ssn_num'               => __('general_content.ssn_num_trans_key'),
                             'nic_num'               => __('general_content.nic_num_trans_key'),
                             'adress_section'        => __('general_content.adress_section_trans_key'),
                             'adress'                => __('general_content.adress_trans_key'),
                             'city'                  => __('general_content.city_trans_key'),
                             'postal_code'           => __('general_content.postal_code_trans_key'),
                             'province'              => __('general_content.province_trans_key'),
                             'country'               => __('general_content.country_trans_key'),
                             'custom_section'        => __('general_content.custom_section_trans_key'),
                             'custom'                => __('general_content.custom_trans_key'),
                             'about_you'             => __('general_content.about_you_trans_key'),
                             'update'                => __('general_content.update_trans_key'),
                             'saving'                => __('general_content.saving_trans_key') ?? 'Saving…',
                             'success_account'       => 'Profile updated successfully',
                             'success_information'   => 'Information updated successfully',
                         ]) }}">
                    </div>
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
                <div class="row">
                    <div id="notification-line-app" class="col-md-9"
                         data-notifications="{{ json_encode($notificationsInitial) }}"
                         data-endpoints="{{ json_encode($notificationEndpoints) }}"
                         data-trans="{{ json_encode([
                             'unread_tab'      => __('general_content.notif_tab_unread_trans_key'),
                             'history_tab'     => __('general_content.notif_tab_history_trans_key'),
                             'unread_list'     => __('general_content.unread_list_trans_key'),
                             'read'            => __('general_content.read_trans_key'),
                             'all_read'        => __('general_content.all_read_trans_key'),
                             'no_data'         => __('general_content.no_data_trans_key'),
                             'read_success'    => __('general_content.notif_read_success_trans_key'),
                             'all_read_success'=> __('general_content.notif_all_read_success_trans_key'),
                             'load_more'       => __('general_content.notif_load_more_trans_key'),
                             'loading'         => __('general_content.notif_loading_trans_key'),
                             'history_empty'   => __('general_content.notif_history_empty_trans_key'),
                             'badge_read'      => __('general_content.notif_badge_read_trans_key'),
                             'badge_unread'    => __('general_content.notif_badge_unread_trans_key'),
                         ]) }}">
                    </div>
                    <div class="col-md-3">
                        <form method="POST" action="{{ route('notifications.setting') }}" >
                            @csrf
                            <x-adminlte-card title="{{ __('general_content.notification_choice_trans_key') }}" theme="teal" maximizable>

                                @php
                                $notifTypes = [
                                    'companies'      => ['label' => __('general_content.new_companie_trans_key'),       'app' => 'companies_notification',      'email' => 'companies_email_notification'],
                                    'users'          => ['label' => __('general_content.new_user_trans_key'),           'app' => 'users_notification',          'email' => 'users_email_notification'],
                                    'quotes'         => ['label' => __('general_content.new_quote_trans_key'),          'app' => 'quotes_notification',         'email' => 'quotes_email_notification'],
                                    'orders'         => ['label' => __('general_content.new_order_trans_key'),          'app' => 'orders_notification',         'email' => 'orders_email_notification'],
                                    'non_conformity' => ['label' => __('general_content.new_non_conformitie_trans_key'),'app' => 'non_conformity_notification', 'email' => 'non_conformity_email_notification'],
                                    'return'         => ['label' => __('general_content.new_return_trans_key'),         'app' => 'return_notification',         'email' => 'return_email_notification'],
                                    'pre_order'      => ['label' => __('general_content.new_pre_order_trans_key'),      'app' => 'pre_order_notification',      'email' => 'pre_order_email_notification'],
                                ];
                                @endphp

                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th class="border-0" style="width:55%">{{ __('general_content.notification_type_trans_key') }}</th>
                                            <th class="border-0 text-center" style="width:22%"><i class="fas fa-bell mr-1"></i>App</th>
                                            <th class="border-0 text-center" style="width:23%"><i class="fas fa-envelope mr-1"></i>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($notifTypes as $type)
                                    <tr>
                                        <td class="align-middle small">{{ $type['label'] }}</td>
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="{{ $type['app'] }}"
                                                       name="{{ $type['app'] }}"
                                                       value="1"
                                                       {{ $UserProfil->{$type['app']} ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="{{ $type['app'] }}"></label>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="{{ $type['email'] }}"
                                                       name="{{ $type['email'] }}"
                                                       value="1"
                                                       {{ $UserProfil->{$type['email']} ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="{{ $type['email'] }}"></label>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>

                                <x-slot name="footerSlot">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                </x-slot>
                            </x-adminlte-card>
                        </form>
                    </div>
                </div>
                <div class="row mt-3">
                    <div id="user-auto-email-reports-app" class="col-md-12"
                         data-reports="{{ json_encode($reportsInitial) }}"
                         data-report-types="{{ json_encode($reportTypes) }}"
                         data-endpoints="{{ json_encode($autoEmailEndpoints) }}"
                         data-trans="{{ json_encode([
                             'automatic_email_reports'      => __('general_content.automatic_email_reports_trans_key'),
                             'automatic_email_reports_help' => __('general_content.automatic_email_reports_help_trans_key'),
                             'report'                       => __('general_content.report_trans_key'),
                             'send_time'                    => __('general_content.send_time_trans_key'),
                             'enabled'                      => __('general_content.enabled_trans_key'),
                             'update'                       => __('general_content.update_trans_key'),
                             'saving'                       => __('general_content.saving_trans_key') ?? 'Saving…',
                             'saved'                        => __('general_content.automatic_email_reports_saved_trans_key'),
                         ]) }}">
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="LeaveRequest">
                <div class="row">
                    <div class="col-md-12">
                        <x-adminlte-card title="{{ __('general_content.leave_balances_trans_key') }} — {{ $LeaveSummary['period_label'] }}" theme="info" maximizable collapsible>
                            @include('include.leave-balance-table', [
                                'summary' => $LeaveSummary,
                                'balanceUserId' => $UserProfil->id,
                                'balanceEditable' => false,
                            ])
                        </x-adminlte-card>
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-card title="{{ __('general_content.leave_request_trans_key') }}" theme="primary" maximizable>
                            <div class="table-responsive p-0">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>{{ __('general_content.user_trans_key') }}</th>
                                        <th>{{ __('general_content.leave_type_trans_key') }}</th>
                                        <th>{{ __('general_content.type_trans_key') }}</th>
                                        <th>{{ __('general_content.type_of_day_trans_key') }}</th>
                                        <th>{{__('general_content.status_trans_key') }}</th>
                                        <th>{{ __('general_content.start_date_trans_key') }}</th>
                                        <th>{{ __('general_content.end_date_trans_key') }}</th>
                                        <th class="text-right">{{ __('general_content.leave_days_trans_key') }}</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($TimesAbsences as $TimesAbsence)
                                    <tr>
                                        <td>{{ $TimesAbsence->User['name'] }}</td>
                                        <td>
                                            @if($TimesAbsence->leaveType)
                                                <span class="badge" style="background-color: {{ $TimesAbsence->leaveType->color ?? '#6c757d' }}">&nbsp;</span>
                                                {{ $TimesAbsence->leaveType->label }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
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
                                        <td class="text-right">{{ number_format((float) $TimesAbsence->days_count, 2, ',', ' ') }}</td>
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
                                                        <label>{{ __('general_content.leave_type_trans_key') }}</label>
                                                        <select class="form-control" name="leave_type_id">
                                                            <option value="">--</option>
                                                            @foreach($LeaveTypes as $LeaveType)
                                                                <option value="{{ $LeaveType->id }}" @if($TimesAbsence->leave_type_id == $LeaveType->id) Selected @endif>{{ $LeaveType->label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
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
                                                    <div class="form-group">
                                                        <label>{{ __('general_content.absence_in_hours_trans_key') }}</label>
                                                        <input type="number" step="0.25" min="0" max="24" class="form-control" name="hours_count" value="{{ $TimesAbsence->hours_count }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>{{ __('general_content.comment_trans_key') }}</label>
                                                        <input type="text" class="form-control" name="comment" value="{{ $TimesAbsence->comment }}">
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
                                        <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                                    @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>{{ __('general_content.user_trans_key') }}</th>
                                            <th>{{ __('general_content.leave_type_trans_key') }}</th>
                                            <th>{{ __('general_content.type_trans_key') }}</th>
                                            <th>{{ __('general_content.type_of_day_trans_key') }}</th>
                                            <th>{{__('general_content.status_trans_key') }}</th>
                                            <th>{{ __('general_content.start_date_trans_key') }}</th>
                                            <th>{{ __('general_content.end_date_trans_key') }}</th>
                                            <th class="text-right">{{ __('general_content.leave_days_trans_key') }}</th>
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
                                    <label for="leave_type_id">{{ __('general_content.leave_type_trans_key') }}</label>
                                    <select class="form-control" name="leave_type_id" id="leave_type_id">
                                        <option value="">--</option>
                                        @foreach($LeaveTypes as $LeaveType)
                                            <option value="{{ $LeaveType->id }}">{{ $LeaveType->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
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
                                <div class="form-group">
                                    <label for="hours_count">{{ __('general_content.absence_in_hours_trans_key') }}</label>
                                    <input type="number" step="0.25" min="0" max="24" class="form-control" name="hours_count" id="hours_count">
                                </div>
                                <div class="form-group">
                                    <label for="comment">{{ __('general_content.comment_trans_key') }}</label>
                                    <input type="text" class="form-control" name="comment" id="comment">
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
            <div class="tab-pane" id="MyDocuments">
                <x-adminlte-card title="{{ __('general_content.documents_trans_key') }}" theme="primary" maximizable>
                    <p class="text-muted">
                        <i class="fas fa-lock"></i> {{ __('general_content.hr_documents_self_hint_trans_key') }}
                    </p>
                    @include('include.file-manager-mount', [
                        'fileableType' => 'user',
                        'fileableId' => $UserProfil->id,
                        'fileRoles' => \App\Services\Files\FileRole::forHumanResources(),
                    ])
                </x-adminlte-card>
            </div>
        </div>
    </div>
</div>
<!-- /.card -->
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
<script>
    (function () {
        var hash = window.location.hash;
        if (hash) {
            // Remove Bootstrap's default active state before it renders
            $('.nav-pills .nav-link').removeClass('active');
            $('.tab-pane').removeClass('active show');
            // Activate the target tab immediately (no animation needed)
            $('a[href="' + hash + '"]').addClass('active');
            $(hash).addClass('active show');
        }
        // Keep hash in sync when switching tabs
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            history.replaceState(null, null, $(e.target).attr('href'));
        });
    })();
</script>
@stop
