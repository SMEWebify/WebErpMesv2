@extends('adminlte::page')

@section('title', __('general_content.requests_for_quotation_list_trans_key')   . ' - ' . $PurchaseQuotation->code)

@section('content_header')
  <x-Content-header-previous-button
    h1="{{ __('general_content.requests_for_quotation_list_trans_key')}} : {{ $PurchaseQuotation->code }}"
    previous="{{ $previousUrl }}"
    list="{{ route('purchases.quotation') }}"
    next="{{ $nextUrl }}">
      <x-slot name="help"><x-guide-help guide="achats" section="consulter-le-fournisseur-la-demande-de-prix" /></x-slot>
  </x-Content-header-previous-button>
@stop

@section('right-sidebar')

@section('content')
@include('include.alert-result')
<div
    id="purchases-quotation-show-app"
    data-quotation="{{ json_encode($reactProps['quotation']) }}"
    data-offers="{{ json_encode($reactProps['offers']) }}"
    data-suppliers="{{ json_encode($reactProps['suppliers']) }}"
    data-supplier-qualification="{{ json_encode($reactProps['supplierQualification']) }}"
    data-addresses="{{ json_encode($reactProps['addresses']) }}"
    data-contacts="{{ json_encode($reactProps['contacts']) }}"
    data-affair-options="{{ json_encode($reactProps['affairOptions']) }}"
    data-currency="{{ $reactProps['currency'] }}"
    data-pdf-languages="{{ json_encode(['current' => app()->getLocale(), 'list' => config('pdf.languages', [])]) }}"
    data-endpoints="{{ json_encode($reactEndpoints) }}"
    data-trans="{{ json_encode($reactTrans + ['ui' => __('purchase_ui')]) }}"
></div>
{{-- Offre PDF du fournisseur et autres pièces de la demande de prix --}}
<div class="card card-outline card-secondary mb-3">
  <div class="card-header">
    <h3 class="card-title">{{ __('general_content.documents_trans_key') }}</h3>
  </div>
  <div class="card-body">
    @include('include.file-manager-mount', [
      'fileableType' => 'purchase-quotation',
      'fileableId'   => $PurchaseQuotation->id,
      'fileRoles'    => \App\Services\Files\FileRole::forPurchaseQuotations(),
    ])
  </div>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
