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
      <!--<li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.quote_line_trans_key') }}</a></li>-->
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
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <form method="POST" action="{{ route('opportunities.update', ['id' => $Opportunity->id]) }}" enctype="multipart/form-data">
                @csrf 
                <div class="card card-body">
                  <div class="row">
                      <div class="col-6">
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
                      <div class="col-12">
                        @include('include.form.form-select-companie',['companiesId' =>  $Opportunity->companies_id])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-adress',['adressId' =>   $Opportunity->companies_addresses_id])
                      </div>
                      <div class="col-6">
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
                      <div class="col-6">
                        <x-adminlte-input type="number" name="probality" label="{{ __('general_content.probality_trans_key') }}" placeholder="50" value="{{  $Opportunity->probality }}" label-class="text-success">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-percentage"></i>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                      <div class="col-6">
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
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <div class="card-body">
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
              </div>
            </div>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.options_trans_key') }}</h3>
              </div>
              <div class="card-body ">
                <p>
                  <a class="btn btn-success btn-sm" href="{{ route('opportunities.store.quote', ['id' => $Opportunity->id ]) }}">
                    <i class="fas fa-folder"></i>
                    {{ __('general_content.new_quote_trans_key') }}
                  </a>
                </p>
                @forelse($Opportunity->Quotes as $Quote)
                  <hr>
                  <x-ButtonTextView route="{{ route('quotes.show', ['id' => $Quote->id])}}" />
                  <x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" /><br/>
                @empty
                {{ __('general_content.no_data_trans_key') }}
                @endforelse
              </div>
            </div>
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.documents_trans_key') }}</h3>
              </div>
              <div class="card-body">
                <form action="{{ route('file.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="far fa-file"></i></span>
                        </div>
                        <div class="custom-file">
                          <input type="hidden" name="opportunities_id" value="{{ $Opportunity->id }}" >
                          <input type="file" name="file" class="custom-file-input" id="chooseFile">
                          <label class="custom-file-label" for="chooseFile">{{ __('general_content.choose_file_trans_key') }}</label>
                        </div>
                        <div class="input-group-append">
                          <button type="submit" name="submit" class="btn btn-success">
                            {{ __('general_content.upload_trans_key') }} 
                          </button>
                        </div>
                      </div>
                </form>
                <h5 class="mt-5 text-muted">{{ __('general_content.attached_file_trans_key') }} </h5>
                <ul class="list-unstyled">
                  @forelse ( $Opportunity->files as $file)
                  <li>
                    <a href="{{ asset('/file/'. $file->name) }}" download="{{ $file->original_file_name }}" class="btn-link text-secondary">{{ $file->original_file_name }} -  <small>{{ $file->GetPrettySize() }}</small></a>
                  </li>
                  @empty
                    {{ __('general_content.no_data_trans_key') }}
                  @endforelse
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="Lines">
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