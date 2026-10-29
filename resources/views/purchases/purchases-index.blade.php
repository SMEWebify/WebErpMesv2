@extends('adminlte::page')

@section('title', __('general_content.purchase_list_trans_key'))

@section('content_header')
  @if (config('purchases.require_qualified_supplier'))
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h1 class="mb-0">{{ __('general_content.purchase_list_trans_key') }}</h1>
      <a href="{{ route('purchases.suppliers.qualified.export') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-file-csv me-1"></i>{{ __('supplier_qualification.export') }}
      </a>
    </div>
  @else
    <h1>{{ __('general_content.purchase_list_trans_key') }}</h1>
  @endif
@stop

@section('content')
<div
  id="purchases-index-app"
  data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($reactChart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-top-suppliers='@json($reactTopSuppliers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-fastest-suppliers='@json($reactFastestSuppliers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-slowest-suppliers='@json($reactSlowestSuppliers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-composite-indicators='@json($reactCompositeIndicators, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-suppliers-to-requalify='@json($reactSuppliersToRequalify, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-top-products='@json($reactTopProducts, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
