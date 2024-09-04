@extends('adminlte::page')

@section('title', __('general_content.lead_trans_key'))

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <x-Content-header-previous-button  h1="{{ __('general_content.lead_trans_key') }} : {{  $Lead->id }}" previous="{{ $previousUrl }}" list="{{ route('leads') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')
@livewire('arrow-steps.arrow-lead', ['LeadId' => $Lead->id, 'LeadStatu' => $Lead->statu])
<div class="row">
  <div class="col-md-9">
    @include('include.alert-result')
    <form method="POST" action="{{ route('leads.update', ['id' => $Lead->id]) }}" enctype="multipart/form-data">
      @csrf 
      <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
        @if($Lead->companie['active'] == 1)
          <div class="row">
            <label for="CutomerInfo" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
          </div>
          <div class="row">
            <div class="form-group col-md-12">
              @include('include.form.form-select-companie',['companiesId' =>  $Lead->companies_id])
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-6">
              @include('include.form.form-select-adress',['adressId' =>   $Lead->companies_addresses_id])
            </div>
            <div class="form-group col-md-6">
              @include('include.form.form-select-contact',['contactId' =>   $Lead->companies_contacts_id])
            </div>
          </div>
          @else
          <input type="hidden" name="companies_id" value="{{ $Lead->companies_id }}">
          <input type="hidden" name="customer_reference" value="{{ $Lead->customer_reference }}">
          <input type="hidden" name="companies_addresses_id" value="{{ $Lead->companies_addresses_id }}">
          <input type="hidden" name="companies_contacts_id" value="{{ $Lead->companies_contacts_id }}">
          <x-adminlte-alert theme="info" title="Info">
            The customer <x-CompanieButton id="{{ $Lead->companie['id'] }}" label="{{ $Lead->companie['label'] }}"  /> is currently disabled, you cannot change the you cannot change the customer name, contact and address.
          </x-adminlte-alert>
          @endif
          <div class="row">
            <label for="GeneralInfo">{{ __('general_content.general_information_trans_key') }}</label>
          </div>
          <div class="row">
            <div class="form-group col-md-6">
              <x-adminlte-input type="text" name="source" label="{{ __('general_content.source_trans_key') }}"  value="{{  $Lead->source }}" label-class="text-success">
                <x-slot name="prependSlot">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                </x-slot>
              </x-adminlte-input>
            </div>
            <div class="form-group col-md-6">
              <x-adminlte-input  type="text" name="campaign" label="{{ __('general_content.campaign_trans_key') }}"  value="{{  $Lead->campaign }}" label-class="text-success">
                <x-slot name="prependSlot">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                </x-slot>
              </x-adminlte-input>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              @include('include.form.form-select-user',['userId' =>   $Lead->user_id])
            </div>
            <div class="form-group col-md-6">
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
                @error('priority') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
        </div>
        <div class="row">
          <x-FormTextareaComment  comment="{{ $Lead->comment }}" />
        </div>
        <x-slot name="footerSlot">
          <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
        </x-slot>
      </x-adminlte-card>
    </form>
  </div>
  <div class="col-md-3">
    <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
      @forelse($Lead->Opportunity as $Opportunity)
      <p>{{ __('general_content.opportunity_trans_key') }} : {{ $Opportunity->label }} </p>
      <x-ButtonTextView route="{{ route('opportunities.show', ['id' => $Opportunity->id])}}" />
      @empty
        <p>
          <a class="btn btn-success btn-sm" href="{{ route('leads.store.opportunity', ['id' => $Lead->id ]) }}">
            <i class="fas fa-folder"></i>
            {{ __('general_content.new_opportunities_trans_key') }}
          </a>
        </p>
      @endforelse
    </x-adminlte-card>
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop