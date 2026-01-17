@extends('adminlte::page')

@section('title', __('general_content.requests_for_quotation_list_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <x-Content-header-previous-button  h1="{{ __('general_content.requests_for_quotation_list_trans_key')}} : {{  $PurchaseQuotation->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.quotation') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#PurchaseQuotation" data-toggle="tab">{{  __('general_content.purchase_quotation_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseQuotationLines" data-toggle="tab">{{  __('general_content.purchase_quotation_lines_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="PurchaseQuotation">

        @livewire('arrow-steps.arrow-rfq', ['RFQId' => $PurchaseQuotation->id, 'RFQStatu' => $PurchaseQuotation->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('quotation.update', ['id' => $PurchaseQuotation->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf 
                <div class="row">
                  <div class="form-group col-md-3">
                    <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $PurchaseQuotation->code }}
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.name_quote_request_trans_key'), 'Value' =>  $PurchaseQuotation->label])
                  </div>
                </div>
                <div class="row">
                  <label for="InputWebSite">{{ __('general_content.supplier_info_trans_key') }}</label>
                </div>
                @if( $PurchaseQuotation->companies_contacts_id == 0 & $PurchaseQuotation->companies_addresses_id ==0)
                <x-adminlte-alert theme="info" title="Info">{{  __('general_content.update_valide_trans_key') }}</x-adminlte-alert>
                @endif
                <div class="row">
                  <div class="form-group col-md-6">
                    @include('include.form.form-select-companie',['companiesId' =>  $PurchaseQuotation->companies_id])
                  </div>
                  <div class="form-group col-md-6">
                    
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    @include('include.form.form-select-adress',['adressId' =>   $PurchaseQuotation->companies_addresses_id])
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-select-contact',['contactId' =>   $PurchaseQuotation->companies_contacts_id])
                  </div>
                </div>
                <div class="row">
                  <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                </div>
                <div class="form-group col-md-6">
                    <label for="label">{{ __('general_content.validity_date_trans_key') }}</label>
                    <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $PurchaseQuotation->validity_date }}">
                </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $PurchaseQuotation->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <!-- /.col-md-9-->

          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                      <td style="width:50%"> 
                        {{ __('general_content.requests_for_quotation_list_trans_key')}}
                      </td>
                      <td>
                        @if( $PurchaseQuotation->companies_contacts_id != 0 & $PurchaseQuotation->companies_addresses_id !=0)
                        <x-ButtonTextPDF route="{{ route('pdf.purchase.quotation', ['Document' => $PurchaseQuotation->id])}}" />
                        @else
                        {{  __('general_content.update_valide_trans_key') }}
                        @endif
                      </td>
                  </tr>
                  @if(config('mail.default') && config('mail.from.address'))
                  <tr>
                    <td style="width:50%">{{ __('general_content.email_trans_key') }}</td>
                    <td><x-ButtonTextEmail route="{{ route('email.create', ['type' => 'purchase-quotation', 'id' => $PurchaseQuotation->id]) }}" /></td>
                  </tr>
                  @endif
                  @if($PurchaseQuotation->rfq_group_id)
                  <tr>
                    <td style="width:50%">{{ __('general_content.compare_rfq_trans_key') }}</td>
                    <td>
                      <a class="btn btn-outline-primary btn-sm" href="{{ route('purchases.quotations.compare', ['group' => $PurchaseQuotation->rfq_group_id]) }}">
                        <i class="fas fa-balance-scale"></i> {{ __('general_content.compare_rfq_trans_key') }}
                      </a>
                    </td>
                  </tr>
                  @endif
                  <tr>
                    <td style="width:50%">{{ __('general_content.duplicate_purchase_quotation_trans_key') }}</td>
                    <td>
                      <a class="btn btn-outline-secondary btn-sm" href="{{ route('purchases.quotations.duplicate', ['id' => $PurchaseQuotation->id]) }}">
                        <i class="fas fa-copy"></i> {{ __('general_content.duplicate_purchase_quotation_trans_key') }}
                      </a>
                    </td>
                  </tr>
                </table>
              </div>
            </x-adminlte-card>
            @include('include.email-list', ['mailsList'=> $PurchaseQuotation->emailLogs,])
          </div>
          <!-- /.col-md-3-->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.tab-pane -->    
      @livewire('purchases-quotation-lines', ['purchaseQuotationId' => $PurchaseQuotation->id])
    </div>
    <!-- /.tab-content -->
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')

@stop
