@extends('adminlte::page')

@section('title', __('general_content.quality_trans_key')) 

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
      <h1>{{ __('general_content.quality_trans_key') }}</h1>
   </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#MeasuringDevices" data-toggle="tab">{{ __('general_content.measuring_devices_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Settings" data-toggle="tab">{{ __('general_content.settings_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Dashboard">
      <div class="row">
        <div class="col-lg-2">
          <x-adminlte-small-box title="{{ $totalActionsOpen }}" text="{{ __('general_content.open_trans_key') }} {{ __('general_content.action_trans_key') }}" icon="fas fa-chart-bar text-white"
              theme="teal"/>
        </div>
        <!-- ./col -->
        <div class="col-lg-2">
          <x-adminlte-small-box title="{{ $totalNonConformitiesOpen }}" text="{{ __('general_content.open_trans_key') }} {{ __('general_content.non_conformities_trans_key') }}" icon="fas fa-chart-bar text-white"
              theme="purple"/>
        </div>
        <!-- ./col -->
        <div class="col-lg-2">
          <x-adminlte-small-box title="{{ $totalDerogationsOpen }}" text="{{ __('general_content.open_trans_key') }} {{ __('general_content.derogations_trans_key') }}" icon="fas fa-chart-bar text-white"
              theme="info"/>
        </div>
        <!-- ./col -->
        <div class="col-lg-2">
          <x-adminlte-small-box title="{{ $litigationRate }} %" text="{{ __('general_content.litigation_rate_trans_key') }}" icon="fas fa-chart-bar text-white"
              theme="secondary"/>
        </div>
        <!-- ./col -->
        <div class="col-lg-2">
          <x-adminlte-small-box title="{{ $resolutionTimes }} {{ __('general_content.days_trans_key') }}" text="{{ __('general_content.resolution_time_trans_key') }}" icon="icon fas fa-clock text-white"
              theme="orange"/>
        </div>
        <!-- ./col -->
      </div>
      <hr>
      <div class="row">
        <div class="col-md-4">
          <canvas id="actionChart" height="100"></canvas>
          <canvas id="derogationChart" height="100"></canvas>
          <canvas id="nonConformityChart" height="100"></canvas>
        </div>

        <div class="col-md-4">
          <canvas id="topGeneratorsChart" width="400" height="200"></canvas>
        </div>

        <div class="col-md-4">
            <!-- Nuage de points -->
            <canvas id="radarChart" width="400" height="400"></canvas>
        </div>
      </div>
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="MeasuringDevices">
      <x-InfocalloutComponent note="{{ __('general_content.measuring_devices_note_trans_key') }}"  />
      @include('include.alert-result')
      @if($calibrationOverdueDevices->isNotEmpty())
        <div class="alert alert-danger">
          <strong>{{ __('general_content.calibration_overdue_trans_key') }}</strong>
          <ul class="mb-0">
            @foreach ($calibrationOverdueDevices as $device)
              <li>
                {{ $device->label }} ({{ $device->code }})
                - {{ optional($device->calibration_due_at)->format('d/m/Y') }}
                @if($device->UserManagement)
                  — {{ $device->UserManagement->name }}
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      @endif
      @if($calibrationDueSoonDevices->isNotEmpty())
        <div class="alert alert-warning">
          <strong>{{ __('general_content.calibration_due_soon_trans_key') }}</strong>
          <p class="mb-1">{{ __('general_content.calibration_due_soon_description_trans_key', ['days' => $calibrationAlertThresholdDays]) }}</p>
          <ul class="mb-0">
            @foreach ($calibrationDueSoonDevices as $device)
              <li>
                {{ $device->label }} ({{ $device->code }})
                - {{ optional($device->calibration_due_at)->format('d/m/Y') }}
                @if($device->UserManagement)
                  — {{ $device->UserManagement->name }}
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      @endif
      <div class="row">
        <div class="col-md-7">
          <x-adminlte-card title="{{ __('general_content.measuring_devices_trans_key') }}" theme="primary" maximizable>
            <div class="table-responsive p-0">
              <table class="table  table-striped table-hover">
                <thead>
                <tr>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{__('general_content.label_trans_key') }}</th>
                  <th>{{ __('general_content.ressource_trans_key') }}</th>
                  <th>{{ __('general_content.user_trans_key') }}</th>
                  <th>{{ __('general_content.serial_number_trans_key') }}</th>
                  <th>{{ __('general_content.calibrated_at_trans_key') }}</th>
                  <th>{{ __('general_content.calibration_due_at_trans_key') }}</th>
                  <th>{{ __('general_content.calibration_status_trans_key') }}</th>
                  <th>{{ __('general_content.calibration_provider_trans_key') }}</th>
                  <th>{{ __('general_content.location_trans_key') }}</th>
                  <th>{{ __('general_content.capability_index_trans_key') }}</th>
                  <th>{{__('general_content.created_at_trans_key') }}</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                  @forelse ($QualityControlDevices as $QualityControlDevice)
                  <tr>
                    <td>{{ $QualityControlDevice->code }}</td>
                    <td>{{ $QualityControlDevice->label }}</td>
                    <td>{{ $QualityControlDevice->service['label'] }}</td>
                    <td><img src="{{ Avatar::create($QualityControlDevice->UserManagement['name'])->toBase64() }}" /></td>
                    <td>{{ $QualityControlDevice->serial_number }}</td>
                    <td>{{ optional($QualityControlDevice->calibrated_at)->format('d/m/Y') ?? __('general_content.not_available_trans_key') }}</td>
                    <td>
                      @if($QualityControlDevice->calibration_due_at)
                        @php
                          $badgeClass = $QualityControlDevice->calibration_alert_class ? 'badge-' . $QualityControlDevice->calibration_alert_class : 'badge-success';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $QualityControlDevice->calibration_due_at->format('d/m/Y') }}</span>
                      @else
                        {{ __('general_content.not_available_trans_key') }}
                      @endif
                    </td>
                    <td>{{ $QualityControlDevice->calibration_status ?? __('general_content.not_available_trans_key') }}</td>
                    <td>{{ $QualityControlDevice->calibration_provider ?? __('general_content.not_available_trans_key') }}</td>
                    <td>{{ $QualityControlDevice->location ?? __('general_content.not_available_trans_key') }}</td>
                    <td>
                      @if(! is_null($QualityControlDevice->capability_index))
                        {{ number_format($QualityControlDevice->capability_index, 3) }}
                      @else
                        {{ __('general_content.not_available_trans_key') }}
                      @endif
                    </td>
                    <td>{{ $QualityControlDevice->GetPrettyCreatedAttribute() }}</td>
                    <td>
                      <!-- Button Modal -->
                      <x-ButtonTextEdit :modalTarget="'QualityControlDevice' . $QualityControlDevice->id" />
                      <!-- Modal {{ $QualityControlDevice->id }} -->
                      <x-adminlte-modal id="QualityControlDevice{{ $QualityControlDevice->id }}" title="Update {{ $QualityControlDevice->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('quality.device.update', ['id' => $QualityControlDevice->id]) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="row">
                            <div class="form-group col-md-6">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityControlDevice->label }}">
                              </div>
                            </div>
                            <div class="form-group col-md-6">
                              <label for="service_id_{{ $QualityControlDevice->id }}">{{ __('general_content.service_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="service_id" id="service_id_{{ $QualityControlDevice->id }}">
                                  @foreach ($ServicesSelect as $item)
                                  <option value="{{ $item->id }}"  @if($QualityControlDevice->service_id == $item->id  ) Selected @endif>{{ $item->label }}</option>
                                  @endforeach
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="form-group col-md-6">
                              <label for="user_id_{{ $QualityControlDevice->id }}">{{ __('general_content.user_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" name="user_id" id="user_id_{{ $QualityControlDevice->id }}">
                                  @foreach ($userSelect as $item)
                                  <option value="{{ $item->id }}" @if($QualityControlDevice->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                  @endforeach
                                </select>
                              </div>
                            </div>
                            <div class="form-group col-md-6">
                              <label for="label">{{ __('general_content.serial_number_trans_key') }}</label>
                              <input type="text" class="form-control"  name="serial_number" id="serial_number" placeholder="{{ __('general_content.serial_number_trans_key') }}" value="{{ $QualityControlDevice->serial_number }}">
                            </div>
                          <!-- /.row -->
                          </div>
                          <div class="row">
                            <div class="form-group col-md-6">
                              <label for="calibrated_at_{{ $QualityControlDevice->id }}">{{ __('general_content.calibrated_at_trans_key') }}</label>
                              <input type="date" class="form-control" name="calibrated_at" id="calibrated_at_{{ $QualityControlDevice->id }}" value="{{ optional($QualityControlDevice->calibrated_at)->format('Y-m-d') }}">
                            </div>
                            <div class="form-group col-md-6">
                              <label for="calibration_due_at_{{ $QualityControlDevice->id }}">{{ __('general_content.calibration_due_at_trans_key') }}</label>
                              <input type="date" class="form-control" name="calibration_due_at" id="calibration_due_at_{{ $QualityControlDevice->id }}" value="{{ optional($QualityControlDevice->calibration_due_at)->format('Y-m-d') }}">
                            </div>
                          </div>
                          <div class="row">
                            <div class="form-group col-md-6">
                              <label for="calibration_status_{{ $QualityControlDevice->id }}">{{ __('general_content.calibration_status_trans_key') }}</label>
                              <input type="text" class="form-control" name="calibration_status" id="calibration_status_{{ $QualityControlDevice->id }}" value="{{ $QualityControlDevice->calibration_status }}">
                            </div>
                            <div class="form-group col-md-6">
                              <label for="calibration_provider_{{ $QualityControlDevice->id }}">{{ __('general_content.calibration_provider_trans_key') }}</label>
                              <input type="text" class="form-control" name="calibration_provider" id="calibration_provider_{{ $QualityControlDevice->id }}" value="{{ $QualityControlDevice->calibration_provider }}">
                            </div>
                          </div>
                          <div class="row">
                            <div class="form-group col-md-6">
                              <label for="location_{{ $QualityControlDevice->id }}">{{ __('general_content.location_trans_key') }}</label>
                              <input type="text" class="form-control" name="location" id="location_{{ $QualityControlDevice->id }}" value="{{ $QualityControlDevice->location }}">
                            </div>
                            <div class="form-group col-md-6">
                              <label for="capability_index_{{ $QualityControlDevice->id }}">{{ __('general_content.capability_index_trans_key') }}</label>
                              <input type="number" step="0.001" min="0" class="form-control" name="capability_index" id="capability_index_{{ $QualityControlDevice->id }}" value="{{ $QualityControlDevice->capability_index }}">
                            </div>
                          </div>
                          <div class="form-group">
                            <div class="col-md-12">
                              <label for="picture">{{ __('general_content.picture_trans_key') }}</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="far fa-image"></i></span>
                                  </div>
                                  <div class="custom-file">
                                      <input type="file" class="custom-file-input" name="picture" id="picture">
                                      <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                                  </div>
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
                    <x-EmptyDataLine col="13" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{__('general_content.label_trans_key') }}</th>
                  <th>{{ __('general_content.ressource_trans_key') }}</th>
                  <th>{{ __('general_content.user_trans_key') }}</th>
                  <th>{{ __('general_content.serial_number_trans_key') }}</th>
                  <th>{{ __('general_content.calibrated_at_trans_key') }}</th>
                  <th>{{ __('general_content.calibration_due_at_trans_key') }}</th>
                  <th>{{ __('general_content.calibration_status_trans_key') }}</th>
                  <th>{{ __('general_content.calibration_provider_trans_key') }}</th>
                  <th>{{ __('general_content.location_trans_key') }}</th>
                  <th>{{ __('general_content.capability_index_trans_key') }}</th>
                  <th>{{__('general_content.created_at_trans_key') }}</th>
                  <th></th>
                </tr>
                </tfoot>
              </table>
            </div>
          </x-adminlte-card>
          <div class="row">
            <div class="form-group col-md-5">
            {{ $QualityControlDevices->links() }}
            </div>
          <!-- /.row -->
          </div>
        <!-- /.col-md-7 card-primary -->
        </div>
        <div class="col-md-5">
          <form method="POST" action="{{ route('quality.device.create')}}" enctype="multipart/form-data">
            <x-adminlte-card title="{{ __('general_content.new_measuring_devices_trans_key') }}" theme="secondary" maximizable>
              @csrf
              <div class="row">
                <div class="form-group col-md-4">
                  <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                    <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                  </div>
                </div>
                <div class="form-group col-md-4">
                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control"  name="label" id="label" placeholder="{{__('general_content.label_trans_key') }}">
                  </div>
                </div>
                <div class="form-group col-md-4">
                  @include('include.form.form-select-service',['serviceId' => null  ])
                </div>
              </div>
              <div class="row">
                <div class="form-group col-md-4">
                  @include('include.form.form-select-user',['userId' =>   null])
                </div>
                <div class="form-group col-md-4">
                  <label for="label">{{ __('general_content.serial_number_trans_key') }}</label>
                  <input type="text" class="form-control"  name="serial_number" id="serial_number" placeholder="{{ __('general_content.serial_number_trans_key') }}">
                </div>
              <!-- /.row -->
              </div>
              <div class="row">
                <div class="form-group col-md-4">
                  <label for="calibrated_at">{{ __('general_content.calibrated_at_trans_key') }}</label>
                  <input type="date" class="form-control" name="calibrated_at" id="calibrated_at">
                </div>
                <div class="form-group col-md-4">
                  <label for="calibration_due_at">{{ __('general_content.calibration_due_at_trans_key') }}</label>
                  <input type="date" class="form-control" name="calibration_due_at" id="calibration_due_at">
                </div>
                <div class="form-group col-md-4">
                  <label for="calibration_status">{{ __('general_content.calibration_status_trans_key') }}</label>
                  <input type="text" class="form-control" name="calibration_status" id="calibration_status" placeholder="{{ __('general_content.calibration_status_trans_key') }}">
                </div>
              </div>
              <div class="row">
                <div class="form-group col-md-4">
                  <label for="calibration_provider">{{ __('general_content.calibration_provider_trans_key') }}</label>
                  <input type="text" class="form-control" name="calibration_provider" id="calibration_provider" placeholder="{{ __('general_content.calibration_provider_trans_key') }}">
                </div>
                <div class="form-group col-md-4">
                  <label for="location">{{ __('general_content.location_trans_key') }}</label>
                  <input type="text" class="form-control" name="location" id="location" placeholder="{{ __('general_content.location_trans_key') }}">
                </div>
                <div class="form-group col-md-4">
                  <label for="capability_index">{{ __('general_content.capability_index_trans_key') }}</label>
                  <input type="number" step="0.001" min="0" class="form-control" name="capability_index" id="capability_index" placeholder="{{ __('general_content.capability_index_trans_key') }}">
                </div>
              </div>
              <div class="form-group">
                <div class="col-md-8">
                  <label for="picture">{{ __('general_content.picture_trans_key') }}</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                  <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="far fa-image"></i></span>
                      </div>
                      <div class="custom-file">
                          <input type="file" class="custom-file-input" name="picture" id="picture">
                          <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                      </div>
                  </div>
                </div>
              <!-- /.form-group -->
              </div>
              <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </div>
            </x-adminlte-card>
          </form>
        </div>
        <!-- /.card secondary -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.tab-pane -->
    <div class="tab-pane" id="Settings">
      <x-InfocalloutComponent note="{{ __('general_content.quality_setting_note_trans_key') }}"  />
      @include('include.alert-result')
      <x-adminlte-card title="{{ __('general_content.failure_trans_key') }}" theme="teal"  collapsible="collapsed" removable maximizable>
        <div class="row">
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('general_content.failure_type_trans_key') }}" theme="primary" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('general_content.external_id_trans_key') }}</th>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($QualityFailures as $QualityFailure)
                    <tr>
                      <td>{{ $QualityFailure->code }}</td>
                      <td>{{ $QualityFailure->label }}</td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <x-ButtonTextEdit :modalTarget="'QualityFailure' . $QualityFailure->id" />
                        <!-- Modal {{ $QualityFailure->id }} -->
                        <x-adminlte-modal id="QualityFailure{{ $QualityFailure->id }}" title="Update {{ $QualityFailure->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('quality.failure.update', ['id' => $QualityFailure->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityFailure->label }}">
                                </div>
                              </div>
                            </div>
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                      <x-EmptyDataLine col="3" text=" {{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                </table>
              </div>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <form  method="POST" action="{{ route('quality.failure.create') }}" class="form-horizontal">
              <x-adminlte-card title="{{ __('general_content.new_failure_trans_key') }}" theme="secondary" maximizable>
                @csrf
                <div class="form-group">
                  <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                    <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                  </div>
                </div>
                <div class="form-group">
                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                  </div>
                </div>
                <div class="card-footer">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
              </x-adminlte-card>
            </form>
          </div>
        <!-- /.row -->
        </div>
      </x-adminlte-card>
      <x-adminlte-card title="{{ __('general_content.cause_trans_key') }}" theme="purple"  collapsible="collapsed" removable maximizable>
            <div class="row">
              <div class="col-md-6">
                <x-adminlte-card title="{{ __('general_content.cause_type_trans_key') }}" theme="primary" maximizable>
                  <div class="table-responsive p-0">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>{{ __('general_content.external_id_trans_key') }}</th>
                          <th>{{__('general_content.label_trans_key') }}</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse ($QualityCauses as $QualityCause)
                        <tr>
                          <td>{{ $QualityCause->code }}</td>
                          <td>{{ $QualityCause->label }}</td>
                          <td class=" py-0 align-middle">
                            <!-- Button Modal -->
                            <x-ButtonTextEdit :modalTarget="'QualityCause' . $QualityCause->id" />
                            <!-- Modal {{ $QualityCause->id }} -->
                            <x-adminlte-modal id="QualityCause{{ $QualityCause->id }}" title="Update {{ $QualityCause->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                              <form method="POST" action="{{ route('quality.cause.update', ['id' => $QualityCause->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                  <div class="form-group">
                                    <label for="label">{{__('general_content.label_trans_key') }}</label>
                                    <div class="input-group">
                                      <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                      </div>
                                      <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityCause->label }}">
                                    </div>
                                  </div>
                                </div>
                                <div class="card-footer">
                                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </div>
                              </form>
                            </x-adminlte-modal>
                          </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                      </tbody>
                    </table>
                  </div>
              </x-adminlte-card>
            </div>
            <div class="col-md-6">
              <form  method="POST" action="{{ route('quality.cause.create') }}" class="form-horizontal">
                <x-adminlte-card title="{{ __('general_content.new_cause_trans_key') }}" theme="secondary" maximizable>
                  @csrf
                  <div class="form-group">
                    <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">{{__('general_content.label_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
              </x-adminlte-card>
            </form>
          </div>
        <!-- /.row -->
        </div>
      </x-adminlte-card>
      <x-adminlte-card title="{{ __('general_content.correction_trans_key') }}" theme="orange"  collapsible="collapsed" removable maximizable>
          <div class="row">
            <div class="col-md-6">
              <x-adminlte-card title="{{ __('general_content.correction_type_trans_key') }}" theme="primary" maximizable>
                <div class="able-responsive p-0">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>{{ __('general_content.external_id_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($QualityCorrections as $QualityCorrection)
                      <tr>
                        <td>{{ $QualityCorrection->code }}</td>
                        <td>{{ $QualityCorrection->label }}</td>
                        <td class=" py-0 align-middle">
                          <!-- Button Modal -->
                          <x-ButtonTextEdit :modalTarget="'QualityCorrection' . $QualityCorrection->id" />
                          <!-- Modal {{ $QualityCorrection->id }} -->
                          <x-adminlte-modal id="QualityCorrection{{ $QualityCorrection->id }}" title="Update {{ $QualityCorrection->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                            <form method="POST" action="{{ route('quality.correction.update', ['id' => $QualityCorrection->id]) }}" enctype="multipart/form-data">
                              @csrf
                              <div class="card-body">
                                <div class="form-group">
                                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $QualityCorrection->label }}">
                                  </div>
                                </div>
                              </div>
                              <div class="card-footer">
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                              </div>
                            </form>
                          </x-adminlte-modal>
                        </td>
                      </tr>
                      @empty
                        <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </x-adminlte-card>
            </div>
            <div class="col-md-6">
              <form  method="POST" action="{{ route('quality.correction.create') }}" class="form-horizontal">
                <x-adminlte-card title="{{ __('general_content.new_correction_trans_key') }}" theme="secondary" maximizable>
                  @csrf
                  <div class="form-group">
                    <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">{{__('general_content.label_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </x-adminlte-card>
              </form>
            </div>
          <!-- /.row -->
        </div>
      </x-adminlte-card>
    </div>
  <!-- /.tab-content -->
  </div>
<!-- /.card -->
</div>
@stop

@section('css')
@stop

@section('js')
<script>
  // Fonction pour créer un graphique empilé
  function createStackedBarChart(canvasId, data) {
      var ctx = document.getElementById(canvasId).getContext('2d');
      
      var options = {
          plugins: {
              datalabels: {
                  color: 'white',
                  display: function(context) {
                      return context.dataset.data[context.dataIndex] > 0;
                  }
              }
          },
          scales: {
              x: {
                  stacked: true
              },
              y: {
                  stacked: true
              }
          }
      };

      return new Chart(ctx, {
          type: 'horizontalBar',
          data: data,
          options: options
      });
  }

  // Données pour Quality Derogation
  var derogationData = {
      labels: ['{{ __('general_content.derogations_trans_key') }}'],
      datasets: [
          {
            label: '{{ __('general_content.internal_trans_key') }}',
            data:  [{{ $internalDerogationRate }}],
            backgroundColor: ['rgba(75, 192, 192, 1)',],
            stack: 'Stack 0',
          },
          {
            label: '{{ __('general_content.external_trans_key') }}',
            data:  [{{ $externalDerogationRate }}],
            backgroundColor: ['rgb(255, 205, 86)',],
            stack: 'Stack 0',
          }
      ]
  };

  // Données pour Quality Non-Conformity
  var nonConformityData = {
      labels: ['{{ __('general_content.non_conformities_trans_key') }}'],
      datasets: [
          {
            label: '{{ __('general_content.internal_trans_key') }}',
            data: [{{ $internalNonConformityRate }}],
            backgroundColor: ['rgba(75, 192, 192, 1)'],
            stack: 'Stack 0',
          },
          {
            label: '{{ __('general_content.external_trans_key') }}',
            data:  [{{ $externalNonConformityRate }}],
            backgroundColor: ['rgb(255, 205, 86)',],
            stack: 'Stack 0',
          }
      ]
  };

  // Données pour Quality Action
  var actionData = {
      labels: ['{{ __('general_content.action_trans_key') }}'],
      datasets: [
          {
              label: '{{ __('general_content.internal_trans_key') }}',
              data: [{{ $internalActionRate }}],
              backgroundColor: ['rgba(75, 192, 192, 1)'],
              stack: 'Stack 0',
          },
          {
              label: '{{ __('general_content.external_trans_key') }}',
              data: [{{ $externalActionRate }}],
              backgroundColor: ['rgb(255, 205, 86)',],
              stack: 'Stack 0',
          }
      ]
  };

  // Créer les graphiques empilés
  createStackedBarChart('derogationChart', derogationData);
  createStackedBarChart('nonConformityChart', nonConformityData);
  createStackedBarChart('actionChart', actionData);

  var ctx = document.getElementById('topGeneratorsChart').getContext('2d');
    var topGeneratorsChart = new Chart(ctx, {
        type: 'horizontalBar',
        data: @json($chartData),
        options: {
          scales: {
            x: {
              beginAtZero: true,
                      stepSize: 1,
            },
            y: {
              beginAtZero: true,
                      stepSize: 1,
            }
          }
        },
    });

  // Diagramme Radar
  var radarChart = new Chart(document.getElementById('radarChart'), {
      type: 'radar',
      data: {
        labels: ["{{ __('general_content.in_progress_trans_key') }}", "{{ __('general_content.waiting_customer_data_trans_key') }}",  "{{ __('general_content.validate_trans_key') }}","{{ __('general_content.canceled_trans_key') }}",],
          datasets: [{
              label: '{{ __('general_content.derogations_trans_key') }}',
              data: {!! json_encode(array_values($derogationStatusCounts)) !!},
              backgroundColor: 'rgba(255, 99, 132, 0.2)',
              borderColor: 'rgb(255, 99, 132)',
              borderWidth: 1
          },
          {
              label: '{{ __('general_content.non_conformities_trans_key') }}',
              data: {!! json_encode(array_values($nonConformityStatusCounts)) !!},
              backgroundColor: 'rgba(54, 162, 235, 0.2)',
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
          },
          {
              label: '{{ __('general_content.action_trans_key') }}',
              data: {!! json_encode(array_values($actionStatusCounts)) !!},
              backgroundColor: 'rgb(255, 205, 86, 0.2)',
              borderColor: 'rgb(255, 205, 86)',
              borderWidth: 1
          }]
      },
        options: {
          scale: {
                  ticks: {
                      min: 0,
                      stepSize: 1,
                      suggestedMax: 5
                  }
            }
      }
  });
</script>
@stop
