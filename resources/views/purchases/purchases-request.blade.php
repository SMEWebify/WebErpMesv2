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
<div class="card">
  <div class="card-header">
    {{ __('general_content.purchase_request_trans_key') }}
  </div>
  <div class="card-body">
      <div class="row">
          @foreach ($topRatedSuppliers as $supplier)
              <div class="col-md-2">
                  <div class="text-center">
                      <h5>{{ $supplier->label }}</h5>
                      <p>{{ __('general_content.supplier_rate_trans_key') }}</p>
                      @for ($i = 1; $i <= 5; $i++)
                          @if ($i <= $supplier->averageRating())
                              <span class="badge badge-warning">&#9733;</span>
                          @else
                              <span class="badge badge-info">&#9734;</span>
                          @endif
                      @endfor
                  </div>
              </div>
          @endforeach
      </div>
  </div>
</div>
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