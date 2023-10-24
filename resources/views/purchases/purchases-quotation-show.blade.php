@extends('adminlte::page')

@section('title', __('general_content.requests_for_quotation_list_trans_key'))

@section('content_header')
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
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            @if( $PurchaseQuotation->companies_contacts_id == 0 & $PurchaseQuotation->companies_addresses_id ==0)
            <x-adminlte-alert theme="info" title="Info">{{  __('general_content.update_valide_trans_key') }}</x-adminlte-alert>
            @endif
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <form method="POST" action="{{ route('quotation.update', ['id' => $PurchaseQuotation->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $PurchaseQuotation->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseQuotation->statu ) Selected @endif >{{ __('general_content.open_trans_key') }}</option>
                          <option value="2" @if(2 == $PurchaseQuotation->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                          <option value="3" @if(3 == $PurchaseQuotation->statu ) Selected @endif >{{ __('general_content.delivered_trans_key') }}</option>
                          <option value="4" @if(4 == $PurchaseQuotation->statu ) Selected @endif >{{ __('general_content.partly_delivered_trans_key') }}</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_quote_request_trans_key'), 'Value' =>  $PurchaseQuotation->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.supplier_info_trans_key') }}</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $PurchaseQuotation->companies_id])
                      </div>
                      <div class="col-5">
                        
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $PurchaseQuotation->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $PurchaseQuotation->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                    </div>
                    <div class="col-5">
                        <label for="label">{{ __('general_content.validity_date_trans_key') }}</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $PurchaseQuotation->validity_date }}">
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseQuotation->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </form>
            </div>
            <!-- /.card-->
          </div>
          <!-- /.col-md-9-->

          <div class="col-md-3">
            <div class="card card-secondary">
                <div class="card-header">
                  <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    
                  </div>
                </div>
              </div>
              <div class="card card-warning">
                <div class="card-header">
                  <h3 class="card-title">{{ __('general_content.options_trans_key') }}</h3>
                </div>
                <div class="card-body table-responsive p-0">
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
                  </table>
                </div>
              </div>
          </div>
          <!-- /.col-md-3-->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.tab-pane -->    
      <div class="tab-pane " id="PurchaseQuotationLines">
        <div class="row">
          <div class="col-12 table-responsive">
            <form method="POST" action="{{ route('purchases.orders.store', ['id' => $PurchaseQuotation->id])}}" >
              @csrf
              <table class="table table-striped">
                <thead>
                    <tr>
                      <th>{{ __('general_content.order_trans_key') }}</th>
                      <th>{{ __('general_content.description_trans_key') }}</th>
                      <th>{{ __('general_content.qty_trans_key') }}</th>
                      <th>{{ __('general_content.price_trans_key') }}</th>
                      <th>{{__('general_content.total_price_trans_key') }}</th>
                      <th></th>
                      <th>{{__('general_content.qty_accepted_trans_key') }}</th>
                      <th>{{ __('general_content.qty_canceled_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                      @forelse($PurchaseQuotation->PurchaseQuotationLines as $PurchaseQuotationLine)
                      <tr>
                        <td>
                          <x-OrderButton id="{{ $PurchaseQuotationLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseQuotationLine->tasks->OrderLines->order->code }}"  />
                        </td>
                        <td>#{{ $PurchaseQuotationLine->tasks->id }}  {{ $PurchaseQuotationLine->tasks->label }}</td>
                        <td>{{ $PurchaseQuotationLine->qty_to_order }}</td>
                        <td>{{ $PurchaseQuotationLine->unit_price }} {{ $Factory->curency }}</td>
                        <td>{{ $PurchaseQuotationLine->total_price }} {{ $Factory->curency }}</td>
                        <td>
                          <div class="form-group">
                            <div class="custom-control custom-checkbox">
                              <input type="hidden" value="{{ $PurchaseQuotationLine->tasks->id }}" name="PurchaseQuotationLineTaskid[]" >
                              <input class="custom-control-input" value="{{ $PurchaseQuotationLine->id }}" name="PurchaseQuotationLine[]" id="PurchaseQuotationLine.{{ $PurchaseQuotationLine->id }}" type="checkbox">
                              <label for="PurchaseQuotationLine.{{ $PurchaseQuotationLine->id }}" class="custom-control-label">+</label>
                            </div>
                          </div>
                        </td>
                        <td>{{ $PurchaseQuotationLine->qty_accepted }}</td>
                        <td>{{ $PurchaseQuotationLine->canceled_qty }}</td>
                      </tr>
                    @empty
                      <x-EmptyDataLine col="5" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                </tbody>
                <tfoot>
                      <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.description_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                        <th>{{__('general_content.total_price_trans_key') }}</th>
                        <th>
                            <button type="Submit" class="btn btn-primary">{{ __('general_content.new_order_trans_key') }}</button>
                        </th>
                        <th>{{__('general_content.qty_accepted_trans_key') }}</th>
                        <th>{{ __('general_content.qty_canceled_trans_key') }}</th>
                      </tr>
                </tfoot>
              </table>
            </form>
          </div>
          <!-- /.col-12 table-responsive-->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.tab-pane -->
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