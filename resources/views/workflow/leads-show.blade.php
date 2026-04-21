@extends('adminlte::page')

@section('title', __('general_content.lead_trans_key'))

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <x-Content-header-previous-button  h1="{{ __('general_content.lead_trans_key') }} : {{  $Lead->id }}" previous="{{ $previousUrl }}" list="{{ route('leads') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')
@php
$leadSteps = json_encode([
    ['value' => 1, 'label' => __('general_content.new_trans_key')],
    ['value' => 2, 'label' => __('general_content.assigned_trans_key')],
    ['value' => 3, 'label' => __('general_content.in_progress_trans_key')],
    ['value' => 4, 'label' => __('general_content.converted_trans_key')],
    ['value' => 5, 'label' => __('general_content.lost_trans_key')],
]);
@endphp
<div data-react="arrow-steps"
     data-steps="{{ $leadSteps }}"
     data-statu="{{ $Lead->statu }}"
     data-endpoint="{{ route('leads.json.statu', $Lead->id) }}"
     data-redirect="{{ route('leads.show', $Lead->id) }}"></div>
<div data-react="arrow-steps"
     data-steps="{{ json_encode([['value' => 1, 'label' => __('general_content.burning_trans_key')], ['value' => 2, 'label' => __('general_content.hot_trans_key')], ['value' => 3, 'label' => __('general_content.lukewarm_trans_key')], ['value' => 4, 'label' => __('general_content.cold_trans_key')]]) }}"
     data-statu="{{ $Lead->priority }}"
     data-endpoint="{{ route('leads.json.priority', $Lead->id) }}"
     data-redirect="{{ route('leads.show', $Lead->id) }}"></div>
<x-relational-breadcrumb :entity="$Lead" />
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
        </div>
        <div class="row">
          <div class="col-12">
            @php
            $config = [
                "height" => "200",
                "toolbar" => [
                    // [groupName, [list of button]]
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                ],
            ]
            @endphp
            <x-adminlte-text-editor name="comment" label="{{ __('general_content.comment_trans_key') }}" label-class="text-primary"
                igroup-size="sm" placeholder="..." :config="$config"> 
                {{  $Lead->comment }}
            </x-adminlte-text-editor>
          </div>
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
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop