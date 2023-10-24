@extends('adminlte::page')

@section('title', __('general_content.orders_lines_list_trans_key'))

@section('content_header')
<div class="row mb-2">
  <h1>{{ __('general_content.orders_lines_list_trans_key') }}</h1>
</div>
@stop

@section('right-sidebar')

@section('content')
@livewire('orders-lines-index')
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