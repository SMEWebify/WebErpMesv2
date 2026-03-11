@extends('adminlte::page')

@section('title', __('general_content.accounting_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.accounting_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
    @include('include.alert-result')
    <div class="card-header p-2">
        <ul class="nav nav-pills" id="AccountingNav">
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'payment-conditions' ? 'active' : '' }}" href="{{ route('accounting.paymentConditions') }}">{{ __('general_content.payment_conditions_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'payment-methods' ? 'active' : '' }}" href="{{ route('accounting.paymentMethods') }}">{{ __('general_content.payment_methods_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'vat' ? 'active' : '' }}" href="{{ route('accounting.vats') }}">{{ __('general_content.vat_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'allocations' ? 'active' : '' }}" href="{{ route('accounting.allocations') }}">{{ __('general_content.accounting_allocations_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'deliveries' ? 'active' : '' }}" href="{{ route('accounting.deliveries') }}">{{ __('general_content.delevery_method_trans_key') }}</a></li>
            @can('asset-menu')
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'assets' ? 'active' : '' }}" href="{{ route('accounting.assets') }}">{{ __('general_content.assets_trans_key') }}</a></li>
            @endcan
        </ul>
    </div>
    <div class="p-3">
        @include($partial)
    </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
