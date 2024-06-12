@extends('adminlte::page')

@section('title', __('general_content.user_trans_key'))  

@section('content_header')
    <h1>{{ __('general_content.user_trans_key') }} {{ $User->name }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-3">
        <x-adminlte-card theme="primary" theme-mode="outline">
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
                <li class="list-group-item"><b>{{ __('general_content.leads_trans_key') }}</b> <a class="float-right">{{ $User->getLeadsCountAttribute() }}</a></li> 
                <li class="list-group-item"><b>{{ __('general_content.quotes_list_trans_key') }}</b> <a class="float-right">{{ $User->getQuotesCountAttribute() }}</a></li>
                <li class="list-group-item"><b>{{ __('general_content.orders_list_trans_key') }}</b> <a class="float-right">{{ $User->getOrdersCountAttribute() }}</a></li>
                <li class="list-group-item"><b>{{ __('general_content.non_conformities_trans_key') }}</b> <a class="float-right">{{ $User->getNcCountAttribute() }}</a></li> 
            </ul>
        </x-adminlte-card>

        <x-adminlte-card title="{{ __('general_content.about_trans_key') }} {{ $User->name }}" theme="primary" maximizable>
            @if($User->private_email)
            <strong><i class="fas fa-envelope  mr-1"></i>{{__('general_content.personnal_mail_trans_key') }}</strong>
            <p class="text-muted">{{ $User->private_email }}</p>
            <hr>
            @endif
            @if($User->personnal_phone_number)
            <strong><i class="fas fa-phone  mr-1"></i>{{__('general_content.personnal_phone_trans_key') }}</strong>
            <p class="text-muted">{{ $User->personnal_phone_number }}</p>
            <hr>
            @endif
            @if($User->born_date)
            <strong><i class="far fa-calendar-alt  mr-1"></i>{{ __('general_content.born_date_trans_key') }}</strong>
            <p class="text-muted">{{ $User->born_date }}</p>
            <hr>
            @endif
            @if($User->nationality)
            <strong><i class="fas fa-flag mr-1"></i>{{__('general_content.nationality_trans_key') }}</strong>
            <p class="text-muted">{{ $User->nationality }}</p>
            <hr>
            @endif
            <strong><i class="fas fa-map-marker-alt mr-1"></i>{{__('general_content.user_location_trans_key') }}</strong>
            <p class="text-muted">{{ $User->address1 }}</p>
            <p class="text-muted">{{ $User->address2 }}</p>
            <p class="text-muted">{{ $User->postal_code }} {{ $User->city }} </p>
        </x-adminlte-card>

        @if($User->id != auth()->id() )
        <x-adminlte-card title="{{ __('general_content.blocked_unti_trans_key') }}" theme="warning" maximizable>
            <form method="POST" action="{{ route('human.resources.lock.user', ['id' => $User->id]) }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                        </div>
                        <input type="date" class="form-control"   name="banned_until"  id="banned_until" value="{{ $User->banned_until }}">
                    </div>
                </div>
                <x-slot name="footerSlot">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
            </form>
        </x-adminlte-card>
        @endif

    </div>

    <div class="col-md-9">
        
        @include('include.alert-result')
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">{{__('general_content.hr_information_trans_key') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contract" data-toggle="tab">{{__('general_content.contract_trans_key') }}</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="activity">
                        <form method="POST" action="{{ route('human.resources.update.user', ['id' => $User->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="job_title">{{ __('general_content.job_title_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  name="job_title" id="job_title" placeholder="{{ __('general_content.job_title_trans_key') }}" value="{{ $User->job_title }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="role">{{__('general_content.role_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="role" id="role">
                                            @forelse ($Roles as $Role)
                                            <option value="{{ $Role->name }}" @if($User->getRoleNames() == $Role->name  ) Selected @endif>{{ $Role->name }}</option>
                                            @empty
                                            <option value="">{{__('general_content.no_role_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="pay_grade">{{ __('general_content.pay_grade_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                        </div>
                                        <input type="number" class="form-control"  name="pay_grade" id="pay_grade" placeholder="{{ __('general_content.pay_grade_trans_key') }}" value="{{ $User->pay_grade }}" min="0">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="work_station_id">{{ __('general_content.work_station_id_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control"  name="work_station_id" id="work_station_id" placeholder="{{ __('general_content.work_station_id_trans_key') }}" value="{{ $User->work_station_id }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="joined_date">{{ __('general_content.joined_date_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control"   name="joined_date"  id="joined_date" value="{{ $User->joined_date }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="confirmation_date">{{ __('general_content.confirmation_date_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control"   name="confirmation_date"  id="confirmation_date" value="{{ $User->confirmation_date }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="termination_date">{{__('general_content.termination_date_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control"   name="termination_date"  id="termination_date" value="{{ $User->termination_date }}">
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="employment_status">{{ __('general_content.employment_statu_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="employment_status" id="employment_status">
                                            <option value="1" @if($User->employment_status == 1) Selected @endif>{{__('general_content.undefined_trans_key') }}</option>
                                            <option value="2" @if($User->employment_status == 2) Selected @endif>{{__('general_content.worker_trans_key') }}</option>
                                            <option value="3" @if($User->employment_status == 3) Selected @endif>{{__('general_content.employee_trans_key') }}</option>
                                            <option value="4" @if($User->employment_status == 4) Selected @endif>{{__('general_content.self_employed_trans_key') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="supervisor_id">{{__('general_content.supervisor_trans_key') }}</label>
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
                                    <label for="section_id">{{ __('general_content.section_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-industry"></i></span>
                                        </div>
                                        <select class="form-control" name="section_id" id="section_id">
                                            @forelse ($SectionsSelect as $item)
                                            <option value="{{ $item->id }}" @if($User->section_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                            @empty
                                            <option value="">{{ __('general_content.no_section_trans_key') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                                <div class="form-group">
                                    <label for="statu">{{__('general_content.active_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <select class="form-control" name="statu" id="statu">
                                            <option value="1" @if($User->statu == 1) Selected @endif>{{__('general_content.active_trans_key') }}</option>
                                            <option value="2" @if($User->statu == 2) Selected @endif>{{__('general_content.inactive_trans_key') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- /.form-group -->
                            </div>
                            <div class="card-footer">
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
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
                                                <th>{{__('general_content.status_trans_key') }}</th>
                                                <th>{{__('general_content.type_of_contract_trans_key') }}</th> 
                                                <th>{{ __('general_content.start_date_trans_key') }}</th>
                                                <th>{{__('general_content.weekly_duration_trans_key') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($UserEmploymentContracts as $UserEmploymentContract)
                                            <tr>
                                                <td>
                                                    @if($UserEmploymentContract->statu == 1) <span class="badge badge-warning">{{__('general_content.on_trial_trans_key') }}</span> @endif
                                                    @if($UserEmploymentContract->statu == 2)<span class="badge badge-success">{{__('general_content.asset_trans_key') }}</span> @endif
                                                    @if($UserEmploymentContract->statu == 3)<span class="badge badge-danger">{{__('general_content.closed_trans_key') }}</span> @endif
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
                                                                    <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <select class="form-control" name="statu" id="statu">
                                                                            <option value="1" @if($UserEmploymentContract->statu == 1  ) Selected @endif>{{__('general_content.on_trial_trans_key') }}</option>
                                                                            <option value="2" @if($UserEmploymentContract->statu == 2  ) Selected @endif>{{__('general_content.asset_trans_key') }}</option>
                                                                            <option value="3" @if($UserEmploymentContract->statu == 3  ) Selected @endif>{{__('general_content.closed_trans_key') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="methods_section_id">{{__('general_content.section_concern_trans_key') }}:</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <select class="form-control" name="methods_section_id" id="methods_section_id">
                                                                            @forelse ($SectionsSelect as $item)
                                                                            <option value="{{ $item->id }}" @if($UserEmploymentContract->methods_section_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                                                            @empty
                                                                            <option value="">{{ __('general_content.no_section_trans_key') }}</option>
                                                                            @endforelse
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="signature_date">{{__('general_content.signature_date_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                                        </div>
                                                                        <input type="date" class="form-control"   name="signature_date"  id="signature_date" value="{{ $UserEmploymentContract->signature_date }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="type_of_contract">{{__('general_content.type_of_contract_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <input type="text" class="form-control"  name="type_of_contract" id="type_of_contract" value="{{ $UserEmploymentContract->type_of_contract }}" placeholder="{{__('general_content.type_of_contract_trans_key') }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="start_date">{{ __('general_content.start_date_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                                        </div>
                                                                        <input type="date" class="form-control"   name="start_date"  id="start_date" value="{{ $UserEmploymentContract->start_date }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="duration_trial_period">{{__('general_content.duration_trial_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-stopwatch">{{ __('general_content.day_trans_key') }}</i></span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="duration_trial_period" id="duration_trial_period" placeholder="{{__('general_content.duration_trial_trans_key') }}" value="{{ $UserEmploymentContract->duration_trial_period }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="start_date">{{ __('general_content.end_date_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                                        </div>
                                                                        <input type="date" class="form-control"   name="end_date"  id="end_date" value="{{ $UserEmploymentContract->end_date }}" >
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="weekly_duration">{{__('general_content.weekly_duration_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-stopwatch">{{__('general_content.hour_trans_key') }}</i></span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="weekly_duration" id="weekly_duration" placeholder="{{__('general_content.weekly_duration_trans_key') }}" value="{{ $UserEmploymentContract->weekly_duration }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="position">{{__('general_content.position_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <input type="text" class="form-control"  name="position" id="position" placeholder="{{__('general_content.position_trans_key') }}" value="{{ $UserEmploymentContract->position }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="coefficient">{{__('general_content.coefficient_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                                        </div>
                                                                        <input type="text" class="form-control"  name="coefficient" id="coefficient" placeholder="{{__('general_content.coefficient_trans_key') }}"  value="{{ $UserEmploymentContract->coefficient }}">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="hourly_gross_salary">{{__('general_content.hourly_gross_salary_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="hourly_gross_salary" id="hourly_gross_salary" placeholder="{{__('general_content.hourly_gross_salary_trans_key') }}"  value="{{ $UserEmploymentContract->hourly_gross_salary }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="minimum_monthly_salary">{{__('general_content.minimum_monthly_salary_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="minimum_monthly_salary" id="minimum_monthly_salary" placeholder="{{__('general_content.minimum_monthly_salary_trans_key') }}"  value="{{ $UserEmploymentContract->minimum_monthly_salary }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                                <div class="form-group">
                                                                    <label for="annual_gross_salary">{{__('general_content.annual_gross_salary_trans_key') }} :</label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">{{ $Factory->curency }}</span>
                                                                        </div>
                                                                        <input type="number" class="form-control"  name="annual_gross_salary" id="annual_gross_salary" placeholder="{{__('general_content.annual_gross_salary_trans_key') }}"  value="{{ $UserEmploymentContract->annual_gross_salary }}" step="1" min="0">
                                                                    </div>
                                                                </div>
                                                                <!-- /.form-group -->
                                                            </div>
                                                            <div class="card-footer">
                                                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                                            </div>
                                                        </form>
                                                    </x-adminlte-modal>
                                                </td>
                                            </tr>
                                            @empty
                                            <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>{{__('general_content.status_trans_key') }}</th>
                                                <th>{{__('general_content.type_of_contract_trans_key') }}</th>
                                                <th>{{ __('general_content.start_date_trans_key') }}</th>
                                                <th>{{__('general_content.weekly_duration_trans_key') }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <!-- /.row -->
                            </div>
                            
                            <div class="col-md-6">
                                
                                <x-adminlte-card title="{{ __('general_content.new_contract_trans_key') }}" theme="secondary" maximizable>
                                    <form method="POST" action="{{ route('human.resources.create.contract', ['id' => $User->id]) }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="card-body">
                                            <input type="hidden" name="user_id" id="user_id" value="{{ $User->id }}">
                                            <div class="form-group">
                                                <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                    </div>
                                                    <select class="form-control" name="statu" id="statu">
                                                        <option value="1">{{__('general_content.on_trial_trans_key') }}</option>
                                                        <option value="2">{{__('general_content.asset_trans_key') }}</option>
                                                        <option value="3">{{__('general_content.closed_trans_key') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="methods_section_id">{{__('general_content.section_concern_trans_key') }}:</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                    </div>
                                                    <select class="form-control" name="methods_section_id" id="methods_section_id">
                                                        @forelse ($SectionsSelect as $item)
                                                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                                                        @empty
                                                        <option value="">{{ __('general_content.no_section_trans_key') }}</option>
                                                        @endforelse
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="signature_date">{{__('general_content.signature_date_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" class="form-control"   name="signature_date"  id="signature_date">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="type_of_contract">{{__('general_content.type_of_contract_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control"  name="type_of_contract" id="type_of_contract" placeholder="{{__('general_content.type_of_contract_trans_key') }}">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="start_date">{{ __('general_content.start_date_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" class="form-control"   name="start_date"  id="start_date">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="duration_trial_period">{{__('general_content.duration_trial_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-stopwatch">{{ __('general_content.day_trans_key') }}</i></span>
                                                    </div>
                                                    <input type="number" class="form-control"  name="duration_trial_period" id="duration_trial_period" placeholder="{{__('general_content.duration_trial_trans_key') }}" step="1" min="0">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="start_date">{{ __('general_content.end_date_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" class="form-control"   name="end_date"  id="end_date">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="weekly_duration">{{__('general_content.weekly_duration_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-stopwatch"> {{__('general_content.hour_trans_key') }}</i></span>
                                                    </div>
                                                    <input type="number" class="form-control"  name="weekly_duration" id="weekly_duration" placeholder="{{__('general_content.weekly_duration_trans_key') }}" step="1" min="0">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="position">{{__('general_content.position_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control"  name="position" id="position" placeholder="{{__('general_content.position_trans_key') }}">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="coefficient">{{__('general_content.coefficient_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                                    </div>
                                                    <input type="text" class="form-control"  name="coefficient" id="coefficient" placeholder="{{__('general_content.coefficient_trans_key') }}">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="hourly_gross_salary">{{__('general_content.hourly_gross_salary_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">{{ $Factory->curency }}</span>
                                                    </div>
                                                    <input type="number" class="form-control"  name="hourly_gross_salary" id="hourly_gross_salary" placeholder="{{__('general_content.hourly_gross_salary_trans_key') }}" step="1" min="0">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="minimum_monthly_salary">{{__('general_content.minimum_monthly_salary_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">{{ $Factory->curency }}</span>
                                                    </div>
                                                    <input type="number" class="form-control"  name="minimum_monthly_salary" id="minimum_monthly_salary" placeholder="{{__('general_content.minimum_monthly_salary_trans_key') }}" step="1" min="0">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                            <div class="form-group">
                                                <label for="annual_gross_salary">{{__('general_content.annual_gross_salary_trans_key') }} :</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">{{ $Factory->curency }}</span>
                                                    </div>
                                                    <input type="number" class="form-control"  name="annual_gross_salary" id="annual_gross_salary" placeholder="{{__('general_content.annual_gross_salary_trans_key') }}" step="1" min="0">
                                                </div>
                                            </div>
                                            <!-- /.form-group -->
                                        </div>
                                        <div class="card-footer">
                                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                        </div>
                                    </form>
                                </x-adminlte-card>
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