@extends('adminlte::page')

@section('title', __('general_content.leads_trans_key'))

@section('content_header')
<div class="row mb-2">
  <div class="col-sm-6">
      <h1>{{ __('general_content.leads_trans_key')}}</h1>
  </div>
  <div class="col-sm-6">
      <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalLead">
        {{ __('general_content.new_leads_trans_key')}}
      </button>
  </div>
</div>
@stop

@section('right-sidebar')

@section('content')
  @livewire('leads-index')
@stop

@section('css')
@stop

@section('js')
@stop