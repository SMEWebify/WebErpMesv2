@extends('adminlte::page')

@section('title', __('general_content.purchase_trans_key'))

@section('content_header')
  <x-Content-header-previous-button  h1="{{  __('general_content.purchase_trans_key') }} : {{  $Purchase->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">{{  __('general_content.purchase_info_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">{{  __('general_content.purchase_lines_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            @if( $Purchase->companies_contacts_id == 0 & $Purchase->companies_addresses_id ==0)
            <x-adminlte-alert theme="info" title="Info">{{  __('general_content.update_valide_trans_key') }}</x-adminlte-alert>
            @endif
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <form method="POST" action="{{ route('purchase.update', ['id' => $Purchase->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Purchase->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $Purchase->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                          <option value="2" @if(2 == $Purchase->statu ) Selected @endif >{{ __('general_content.ordered_trans_key') }}</option>
                          <option value="3" @if(3 == $Purchase->statu ) Selected @endif >{{ __('general_content.partly_received_trans_key') }}</option>
                          <option value="4" @if(4 == $Purchase->statu ) Selected @endif >{{ __('general_content.rceived_trans_key') }}</option>
                          <option value="5" @if(5 == $Purchase->statu ) Selected @endif >{{ __('general_content.canceled_trans_key') }}</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_purchase_trans_key'), 'Value' =>  $Purchase->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.supplier_info_trans_key') }}</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $Purchase->companies_id])
                      </div>
                      <div class="col-5">
                        
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $Purchase->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $Purchase->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Purchase->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                    <th>Total:</th>
                    <td>{{ $totalPrices }} {{ $Factory->curency }}</td>
                  </tr>
                </table>
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
                        {{ __('general_content.purchase_trans_key') }}
                      </td>
                      <td>
                        @if( $Purchase->companies_contacts_id != 0 & $Purchase->companies_addresses_id !=0)
                        <x-ButtonTextPDF route="{{ route('pdf.purchase', ['Document' => $Purchase->id])}}" />
                        @else
                        {{  __('general_content.update_valide_trans_key') }}
                        @endif
                      </td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>    
      <div class="tab-pane " id="PurchaseLines">
        <!-- Table row -->
        <div class="row">
          <div class="col-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>{{ __('general_content.order_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th>{{ __('general_content.supplier_ref_trans_key') }}</th>
                  <th>{{ __('general_content.qty_trans_key') }}</th>
                  <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                  <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                  <th>{{ __('general_content.price_trans_key') }}</th>
                  <th>{{ __('general_content.discount_trans_key') }}</th>
                  <th>{{ __('general_content.total_selling_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($Purchase->PurchaseLines as $PurchaseLine)
                  <tr>
                    <td>
                        <x-OrderButton id="{{  $PurchaseLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseLine->tasks->OrderLines->order->code }}"  />
                    </td>
                    <td>#{{ $PurchaseLine->tasks->id }} {{ $PurchaseLine->code }} {{ $PurchaseLine->label }}</td>
                    <td>{{ $PurchaseLine->supplier_ref }}</td>
                    <td>{{ $PurchaseLine->qty }}</td>
                    <td>{{ $PurchaseLine->receipt_qty }}</td>
                    <td>{{ $PurchaseLine->invoiced_qty }}</td>
                    <td>{{ $PurchaseLine->selling_price }} {{ $Factory->curency }}</td>
                    <td>{{ $PurchaseLine->discount }} %</td>
                    <td>{{ $PurchaseLine->total_selling_price }} {{ $Factory->curency }}</td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.supplier_ref_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                    <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
                    <th>{{ __('general_content.discount_trans_key') }}</th>
                    <th>{{ __('general_content.total_selling_trans_key') }}</th>
                  </tr>
                </tfoot>
              </tbody>
            </table>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop