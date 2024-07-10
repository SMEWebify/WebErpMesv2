@extends('adminlte::page')

@section('title', __('general_content.opportunity_trans_key'))

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <x-Content-header-previous-button  h1="{{ __('general_content.opportunity_trans_key') }} : {{  $Opportunity->label }}" previous="{{ $previousUrl }}" list="{{ route('opportunities') }}" next="{{ $nextUrl }}"/>

@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Opportunity" data-toggle="tab">{{ __('general_content.opportunity_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#TimeLine" data-toggle="tab">TimeLine</a></li>
      <li class="nav-item"><a class="nav-link" href="#Activities" data-toggle="tab">{{ __('general_content.activities_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Events" data-toggle="tab">{{ __('general_content.events_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Opportunity">
        @livewire('arrow-steps.arrow-opportunity', ['OpportunityId' => $Opportunity->id, 'OpportunityStatu' => $Opportunity->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('opportunities.update', ['id' => $Opportunity->id]) }}" enctype="multipart/form-data">
              @csrf 
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                <div class="card card-body">
                  <div class="row">
                    <div class="form-group col-md-6">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_opportunity_trans_key'), 'Value' =>  $Opportunity->label])
                      </div>
                    </div>
                  </div>
                @if($Opportunity->companie['active'] == 1)
                  <div class="card card-body">
                    <div class="row">
                      <label for="CutomerInfo" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="form-group col-md-12">
                        @include('include.form.form-select-companie',['companiesId' =>  $Opportunity->companies_id])
                      </div>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-6">
                        @include('include.form.form-select-adress',['adressId' =>   $Opportunity->companies_addresses_id])
                      </div>
                      <div class="form-group col-md-6">
                        @include('include.form.form-select-contact',['contactId' =>   $Opportunity->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  @else
                  <input type="hidden" name="companies_id" value="{{ $Opportunity->companies_id }}">
                  <input type="hidden" name="customer_reference" value="{{ $Opportunity->customer_reference }}">
                  <input type="hidden" name="companies_addresses_id" value="{{ $Opportunity->companies_addresses_id }}">
                  <input type="hidden" name="companies_contacts_id" value="{{ $Opportunity->companies_contacts_id }}">
                  <x-adminlte-alert theme="info" title="Info">
                    The customer <x-CompanieButton id="{{ $Opportunity->companie['id'] }}" label="{{ $Opportunity->companie['label'] }}"  /> is currently disabled, you cannot change the you cannot change the customer name, contact and address.
                  </x-adminlte-alert>
                  @endif
                  <div class="card card-body">
                    <div class="row">
                      <label for="GeneralInfo">{{ __('general_content.general_information_trans_key') }}</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="form-group col-md-6">
                        <x-adminlte-input type="number" name="probality" label="{{ __('general_content.probality_trans_key') }}" placeholder="50" value="{{  $Opportunity->probality }}" label-class="text-success">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-percentage"></i>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                      <div class="form-group col-md-6">
                        <x-adminlte-input  type="number" name="budget" label="{{ __('general_content.budget_trans_key') }}" placeholder="0" value="{{  $Opportunity->budget }}" label-class="text-success">
                          <x-slot name="prependSlot">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Opportunity->comment }}" />
                    </div>
                  </div>
                  <x-slot name="footerSlot">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.historical_trans_key') }}" theme="info" maximizable>
              @if($Opportunity->leads_id)
              <div class="text-muted">
                <h3>Lead #{{ $Opportunity->leads_id }} </h3>
                <p class="text-sm">{{ __('general_content.user_trans_key') }}
                  <b class="d-block">{{ $Opportunity->lead->UserManagement['name'] }}</b>
                </p>
                <p class="text-sm">{{ __('general_content.source_trans_key') }}
                  <b class="d-block">{{ $Opportunity->lead->source }}</b>
                </p>
                <p class="text-sm">{{ __('general_content.campaign_trans_key') }}
                  <b class="d-block">{{ $Opportunity->lead->campaign }}</b>
                </p>
              </div>
              @endif
            </x-adminlte-card>
            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <p>
                <a class="btn btn-success btn-sm" href="{{ route('opportunities.store.quote', ['id' => $Opportunity->id ]) }}">
                  <i class="fas fa-folder"></i>
                  {{ __('general_content.new_quote_trans_key') }}
                </a>
              </p>
              @forelse($Opportunity->Quotes as $Quote)
                <hr>
                <x-QuoteButton id="{{ $Quote->id }}" code="{{ $Quote->code }}"  />
                <x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" /><br/>
              @empty
              {{ __('general_content.no_data_trans_key') }}
              @endforelse
            </x-adminlte-card>
            @include('include.file-store', ['inputName' => "opportunities_id",'inputValue' => $Opportunity->id,'filesList' => $Opportunity->files,])
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="TimeLine">
        <div class="timeline timeline-inverse">
          @php
              $previousDate = null;
          @endphp

          @foreach($timelineData as $item)
            @if ($item['date'] != $previousDate)
            <div class="time-label">
                <span class="bg-info">{{ $item['date'] }}</span>
            </div>
            @endif
            <div>
                <i class="{{ $item['icon'] }}"></i>
                <div class="timeline-item">
                    <span class="time"><i class="far fa-clock"></i> {{ $item['details'] }}</span>
                    <h3 class="timeline-header">{{ $item['content'] }}</h3>
                </div>
            </div>
            @php
              $previousDate = $item['date'];
            @endphp
        @endforeach
          <div>
            <i class="far fa-clock bg-gray"></i>
          </div>
        </div>
      </div>
      <div class="tab-pane " id="Activities">
        <div class="row">
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('general_content.activities_trans_key') }}" theme="primary" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.type_trans_key') }}</th>
                      <th>{{ __('general_content.statu_trans_key') }}</th>
                      <th>{{ __('general_content.priority_trans_key') }}</th>
                      <th>{{ __('general_content.due_date_trans_key') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($ActivitiesList as $Activity)
                    <tr>
                      <td>{{ $Activity->label }}</td>
                      <td>
                        @if($Activity->type  == 1) <span class="badge badge-info">{{ __('general_content.activity_maketing_trans_key') }}</span> @endif
                        @if($Activity->type  == 2) <span class="badge badge-warning">{{ __('general_content.email_send_trans_key') }}</span> @endif
                        @if($Activity->type  == 3) <span class="badge badge-primary">{{ __('general_content.pre_sakes_aactivity_trans_key') }}</span> @endif
                        @if($Activity->type  == 4) <span class="badge badge-success">{{ __('general_content.sales_activity_trans_key') }}</span> @endif
                        @if($Activity->type  == 5) <span class="badge badge-danger">{{ __('general_content.sales_telephone_call_trans_key') }}</span> @endif
                      </td>
                      <td>
                        @if($Activity->statu  == 1) <span class="badge badge-info">{{ __('general_content.no_start_trans_key') }}</span> @endif
                        @if($Activity->statu  == 2) <span class="badge badge-success">{{ __('general_content.in_progress_trans_key') }}</span> @endif
                        @if($Activity->statu  == 3) <span class="badge badge-success">{{ __('general_content.closed_trans_key') }}</span> @endif
                        @if($Activity->statu  == 4) <span class="badge badge-warning">{{ __('general_content.waiting_customer_data_trans_key') }}</span> @endif
                      </td>
                      <td>
                        @if(1 == $Activity->priority )  <span class="badge badge-danger">{{ __('general_content.burning_trans_key') }}</span>@endif
                        @if(2 == $Activity->priority )  <span class="badge badge-warning">{{ __('general_content.hot_trans_key') }}</span>@endif
                        @if(3 == $Activity->priority )  <span class="badge badge-primary">{{ __('general_content.lukewarm_trans_key') }}</span>@endif
                        @if(4 == $Activity->priority )  <span class="badge badge-success">{{ __('general_content.cold_trans_key') }}</span>@endif
                      </td>
                      <td>{{ $Activity->due_date }}</td>
                      <td class="py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Activity{{ $Activity->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $Activity->id }} -->
                        <x-adminlte-modal id="Activity{{ $Activity->id }}" title="Update {{ $Activity->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('opportunities.update.activity', ['id' => $Opportunity->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $Activity->label }}">
                                  <input type="hidden"  name="id"  id="id" value="{{ $Activity->id }}" >
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="type">{{ __('general_content.statu_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                  </div>
                                  <select class="form-control" name="type" id="type">
                                    <option value="1" @if($Activity->type == 1 ) Selected @endif>{{ __('general_content.activity_maketing_trans_key') }}</option>
                                    <option value="2" @if($Activity->type == 2  ) Selected @endif>{{ __('general_content.email_send_trans_key') }}</option>
                                    <option value="3" @if($Activity->type == 3 ) Selected @endif>{{ __('general_content.pre_sakes_aactivity_trans_key') }}</option>
                                    <option value="4" @if($Activity->type == 4  ) Selected @endif>{{ __('general_content.sales_activity_trans_key') }}</option>
                                    <option value="5" @if($Activity->type == 5  ) Selected @endif>{{ __('general_content.sales_telephone_call_trans_key') }}</option>
                                  </select>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="statu">{{ __('general_content.statu_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                  </div>
                                  <select class="form-control" name="statu" id="statu">
                                    <option value="1" @if($Activity->statu == 1 ) Selected @endif>{{ __('general_content.no_start_trans_key') }}</option>
                                    <option value="2" @if($Activity->statu == 2  ) Selected @endif>{{ __('general_content.in_progress_trans_key') }}</option>
                                    <option value="3" @if($Activity->statu == 3 ) Selected @endif>{{ __('general_content.closed_trans_key') }}</option>
                                    <option value="4" @if($Activity->statu == 4  ) Selected @endif>{{ __('general_content.waiting_customer_data_trans_key') }}</option>
                                  </select>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="priority">{{ __('general_content.priority_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-text bg-gradient-success">
                                        <i class="fas fa-exclamation"></i>
                                    </div>
                                    <select class="form-control"  name="priority" id="priority">
                                        <option value="1"  @if($Activity->priority == 1 ) Selected @endif>{{ __('general_content.burning_trans_key') }}</option>
                                        <option value="2"  @if($Activity->priority == 2 ) Selected @endif>{{ __('general_content.hot_trans_key') }}</option>
                                        <option value="3"  @if($Activity->priority == 3 ) Selected @endif>{{ __('general_content.lukewarm_trans_key') }}</option>
                                        <option value="4"  @if($Activity->priority == 4 ) Selected @endif>{{ __('general_content.cold_trans_key') }}</option>
                                    </select>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="due_date">{{ __('general_content.due_date_trans_key') }}</label>
                                <input type="date" class="form-control" name="due_date"  id="due_date"  value="{{ $Activity->due_date }}" >
                              </div>
                              <div class="form-group">
                                <x-FormTextareaComment  comment="{{ $Activity->comment }}" />
                              </div>
                            </div>
                            <x-slot name="footerSlot">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                            </x-slot>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                    <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                  <tfoot>
                    <tr>
                      <th>{{ __('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.type_trans_key') }}</th>
                      <th>{{ __('general_content.statu_trans_key') }}</th>
                      <th>{{ __('general_content.priority_trans_key') }}</th>
                      <th>{{ __('general_content.due_date_trans_key') }}</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <form  method="POST" action="{{ route('opportunities.store.activity', ['id' => $Opportunity->id]) }}" class="form-horizontal" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.new_activity_trans_key') }}" theme="secondary" maximizable>
              @csrf
                <div class="form-group">
                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" >
                    <input type="hidden"  name="opportunities_id"  id="opportunities_id" value="{{ $Opportunity->id }}" >
                  </div>
                </div>
                <div class="form-group">
                  <label for="type">{{ __('general_content.statu_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                    </div>
                    <select class="form-control" name="type" id="type">
                      <option value="1" >{{ __('general_content.activity_maketing_trans_key') }}</option>
                      <option value="2" >{{ __('general_content.email_send_trans_key') }}</option>
                      <option value="3" >{{ __('general_content.pre_sakes_aactivity_trans_key') }}</option>
                      <option value="4" >{{ __('general_content.sales_activity_trans_key') }}</option>
                      <option value="5" >{{ __('general_content.sales_telephone_call_trans_key') }}</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="priority">{{ __('general_content.priority_trans_key') }}</label>
                  <div class="input-group">
                      <div class="input-group-text bg-gradient-success">
                          <i class="fas fa-exclamation"></i>
                      </div>
                      <select class="form-control"  name="priority" id="priority">
                          <option value="1" >{{ __('general_content.burning_trans_key') }}</option>
                          <option value="2" >{{ __('general_content.hot_trans_key') }}</option>
                          <option value="3" >{{ __('general_content.lukewarm_trans_key') }}</option>
                          <option value="4" >{{ __('general_content.cold_trans_key') }}</option>
                      </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="due_date">{{ __('general_content.due_date_trans_key') }}</label>
                  <input type="date" class="form-control" name="due_date"  id="due_date"  >
                </div>
                <div class="form-group">
                  <label>{{ __('general_content.comment_trans_key') }}</label>
                  <textarea class="form-control" rows="3" name="comment"  placeholder="..."></textarea>
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
        </div>
      </div>
      <div class="tab-pane " id="Events">
        <div class="row">
          <div class="col-md-6 card-primary">
            <x-adminlte-card title="{{ __('general_content.events_trans_key') }}" theme="primary" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>{{ __('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.type_trans_key') }}</th>
                      <th>{{ __('general_content.start_date_trans_key') }}</th>
                      <th>{{ __('general_content.end_date_trans_key') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($EventsList as $Event)
                    <tr>
                      <td>{{ $Event->label }}</td>
                      <td>
                        @if($Event->type  == 1) <span class="badge badge-info">{{ __('general_content.activity_maketing_trans_key') }}</span> @endif
                        @if($Event->type  == 2) <span class="badge badge-warning">{{ __('general_content.internal_meeting_trans_key') }}</span> @endif
                        @if($Event->type  == 3) <span class="badge badge-primary">{{ __('general_content.onsite_visite_trans_key') }}</span> @endif
                        @if($Event->type  == 4) <span class="badge badge-success">{{ __('general_content.sales_meeting_trans_key') }}</span> @endif
                      </td>
                      <td>{{ $Event->start_date }}</td>
                      <td>{{ $Event->end_date }}</td>
                      <td class="py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Event{{ $Event->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $Event->id }} -->
                        <x-adminlte-modal id="Event{{ $Event->id }}" title="Update {{ $Event->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('opportunities.update.event', ['id' => $Opportunity->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                              <div class="form-group">
                                <label for="label">{{__('general_content.label_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                  </div>
                                  <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $Event->label }}">
                                  <input type="hidden"  name="id"  id="id" value="{{ $Event->id }}" >
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="type">{{ __('general_content.statu_trans_key') }}</label>
                                <div class="input-group">
                                  <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                  </div>
                                  <select class="form-control" name="type" id="type">
                                    <option value="1" @if($Event->type == 1 ) Selected @endif>{{ __('general_content.activity_maketing_trans_key') }}</option>
                                    <option value="2" @if($Event->type == 2  ) Selected @endif>{{ __('general_content.internal_meeting_trans_key') }}</option>
                                    <option value="3" @if($Event->type == 3 ) Selected @endif>{{ __('general_content.onsite_visite_trans_key') }}</option>
                                    <option value="4" @if($Event->type == 4  ) Selected @endif>{{ __('general_content.sales_meeting_trans_key') }}</option>
                                  </select>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="start_date">{{ __('general_content.start_date_trans_key') }}</label>
                                <input type="date" class="form-control" name="start_date"  id="start_date"  value="{{ $Event->start_date }}" >
                              </div>
                              <div class="form-group">
                                <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                                <input type="date" class="form-control" name="end_date"  id="end_date"  value="{{ $Event->end_date }}" >
                              </div>
                              <div class="form-group">
                                <x-FormTextareaComment  comment="{{ $Event->comment }}" />
                              </div>
                            </div>
                            <x-slot name="footerSlot">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                            </x-slot>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                    <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                  <tfoot>
                    <tr>
                      <th>{{ __('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.type_trans_key') }}</th>
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
            <form  method="POST" action="{{ route('opportunities.store.event', ['id' => $Opportunity->id]) }}" class="form-horizontal" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.new_event_trans_key') }}" theme="secondary" maximizable>
                @csrf
                <div class="form-group">
                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" >
                    <input type="hidden"  name="opportunities_id"  id="opportunities_id" value="{{ $Opportunity->id }}" >
                  </div>
                </div>
                <div class="form-group">
                  <label for="type">{{ __('general_content.statu_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                    </div>
                    <select class="form-control" name="type" id="type">
                      <option value="1" >{{ __('general_content.activity_maketing_trans_key') }}</option>
                      <option value="2" >{{ __('general_content.internal_meeting_trans_key') }}</option>
                      <option value="3" >{{ __('general_content.onsite_visite_trans_key') }}</option>
                      <option value="4" >{{ __('general_content.sales_meeting_trans_key') }}</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="start_date">{{ __('general_content.start_date_trans_key') }}</label>
                  <input type="date" class="form-control" name="start_date"  id="start_date"  >
                </div>
                <div class="form-group">
                  <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                  <input type="date" class="form-control" name="end_date"  id="end_date"  >
                </div>
                <div class="form-group">
                  <label>{{ __('general_content.comment_trans_key') }}</label>
                  <textarea class="form-control" rows="3" name="comment"  placeholder="..."></textarea>
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
        </div>
      </div>

    </div>
  <!-- /.card-body -->
  </div>
<!-- /.card -->

@stop

@section('css')
@stop

@section('js')
@stop