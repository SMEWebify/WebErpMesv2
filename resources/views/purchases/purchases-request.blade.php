@extends('adminlte::page')

@section('title', __('general_content.purchase_request_trans_key'))

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>{{ __('general_content.purchase_request_trans_key') }}</h1>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')
@livewire('purchases-request')

@stop

@section('css')
@stop

@section('js')
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip({
          html:true
      })
  })
</script>
@stop