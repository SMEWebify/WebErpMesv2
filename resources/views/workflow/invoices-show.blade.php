@extends('adminlte::page')

@section('title', __('general_content.invoices_trans_key'))

@section('content_header')
  <x-Content-header-previous-button  h1="{{ __('general_content.invoices_trans_key') }} : {{  $Invoice->code }}" previous="{{ $previousUrl }}" list="{{ route('invoices') }}" next="{{ $nextUrl }}"/>
@stop


@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Invoice" data-toggle="tab">{{ __('general_content.invoice_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#InvoiceLines" data-toggle="tab">{{ __('general_content.invoice_lines_trans_key') }}</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }}</a></li>
      @endif
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Invoice">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('invoices.update', ['id' => $Invoice->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf
                <div class="card card-body">
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Invoice->code }}
                    </div>
                    <div class="form-group col-md-4">
                      <x-adminlte-select name="statu" label="{{ __('general_content.status_trans_key') }}" label-class="text-success" igroup-size="sm">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-success">
                                <i class="fas fa-exclamation"></i>
                            </div>
                        </x-slot>
                        <option value="1" @if(1 == $Invoice->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                        <option value="2" @if(2 == $Invoice->statu ) Selected @endif >{{ __('general_content.send_trans_key') }}</option>
                        <option value="3" @if(3 == $Invoice->statu ) Selected @endif >{{ __('general_content.pending_trans_key') }}</option>
                        <option value="4" @if(4 == $Invoice->statu ) Selected @endif >{{ __('general_content.unpaid_trans_key') }}</option>
                        <option value="5" @if(5 == $Invoice->statu ) Selected @endif >{{ __('general_content.paid_trans_key') }}</option>
                      </x-adminlte-select>
                    </div>
                    <div class="form-group col-md-4">
                      @include('include.form.form-input-label',['label' => __('general_content.label_trans_key'), 'Value' =>  $Invoice->label])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="label">{{ __('general_content.due_date_trans_key') }}</label>
                      <input type="date" class="form-control" name="due_date"  id="due_date" value="{{  $Invoice->due_date }}">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="incoterm">{{ __('general_content.incoterm_trans_key') }}</label>
                      <input type="text" class="form-control" name="incoterm"  id="incoterm" value="{{  $Invoice->incoterm }}">
                    </div>
                  </div>
                </div>
                <div class="card card-body">
                  <div class="row">
                    <x-FormTextareaComment  comment="{{ $Invoice->comment }}" />
                  </div>
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>

          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
              <div class="table-responsive">
                <div class="card-body">
                  {{ __('general_content.created_at_trans_key') }} : {{ $Invoice->GetPrettyCreatedAttribute() }}
                </div>
                <div class="card-body">
                  {{ __('general_content.companie_name_trans_key') }} :  <x-CompanieButton id="{{ $Invoice->companie['id'] }}" label="{{ $Invoice->companie['label'] }}"  />
                </div>
                <div class="card-body">
                  {{ __('general_content.adress_name_trans_key') }} :   {{ $Invoice->adresse['label'] }} - {{ $Invoice->adresse['adress'] }}
                </div>
                <div class="card-body">
                  {{ __('general_content.contact_name_trans_key') }} :  {{ $Invoice->contact['first_name'] }} - {{ $Invoice->contact['name'] }}
                </div>
              </div>
              <div class="card-body">
                @include('include.sub-total-price')
              </div>
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                    <tr>
                        <td style="width:50%"> 
                          {{ __('general_content.invoices_trans_key') }}
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.invoice', ['Document' => $Invoice->id])}}" />
                        </td>
                    </tr>
                </table>
              </div>
            </x-adminlte-card>

            @include('include.file-store', ['inputName' => "invoices_id",'inputValue' => $Invoice->id,'filesList' => $Invoice->files,])
          </div>
        </div>
      </div>       
      <div class="tab-pane " id="InvoiceLines">
        <!-- Table row -->
        <div class="row">
          <div class="col-12 table-responsive">
            <form action="{{ route('credit-notes.store.from.invoice') }}" method="POST">
              @csrf
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.delivery_notes_trans_key') }}</th>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.unit_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
                    <th>{{ __('general_content.discount_trans_key') }}</th>
                    <th>{{ __('general_content.vat_trans_key') }}</th>
                    <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                    @forelse($Invoice->InvoiceLines as $InvoiceLine)
                    <tr>
                      <td>
                        <x-OrderButton id="{{ $InvoiceLine->orderLine->order['id'] }}" code="{{ $InvoiceLine->orderLine->order['code'] }}"  />
                      </td>
                      <td>
                        @if($InvoiceLine->delivery_line_id)
                        <x-DeliveryButton id="{{ $InvoiceLine->deliveryLine->delivery['id'] }}" code="{{ $InvoiceLine->deliveryLine->delivery['code'] }}"  />
                        @else
                        {{ __('general_content.delivered_without_dn_trans_key') }}
                        @endif
                        </td>
                      <td>{{ $InvoiceLine->orderLine['code'] }}</td>
                      <td>{{ $InvoiceLine->orderLine['label'] }}</td>
                      <td>{{ $InvoiceLine->qty }}</td>
                      <td>{{ $InvoiceLine->OrderLine->Unit['label'] }}</td>
                      <td>{{ $InvoiceLine->OrderLine['selling_price'] }} {{ $Factory->curency }}</td>
                      <td>{{ $InvoiceLine->OrderLine['discount'] }} %</td>
                      <td>{{ $InvoiceLine->OrderLine->VAT['rate'] }} %</td>
                      <td>
                        @if(1 == $InvoiceLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.in_progress_trans_key') }}</span>@endif 
                        @if(2 == $InvoiceLine->invoice_status )  <span class="badge badge-primary">{{ __('general_content.send_trans_key') }}</span>@endif
                        @if(3 == $InvoiceLine->invoice_status )  <span class="badge badge-warning">{{ __('general_content.pending_trans_key') }}</span>@endif
                        @if(4 == $InvoiceLine->invoice_status )  <span class="badge badge-danger">{{ __('general_content.unpaid_trans_key') }}</span>@endif
                        @if(5 == $InvoiceLine->invoice_status )  <span class="badge badge-success">{{ __('general_content.paid_trans_key') }}</span>@endif
                      </td>
                      <td>
                          <input type="checkbox" name="selected_invoice_lines[]" value="{{ $InvoiceLine->id }}">
                      </td>
                    </tr>
                    @empty
                      <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                  <tfoot>
                    <tr>
                      <th>{{ __('general_content.order_trans_key') }}</th>
                      <th>{{ __('general_content.delivery_notes_trans_key') }}</th>
                      <th>{{ __('general_content.external_id_trans_key') }}</th>
                      <th>{{ __('general_content.description_trans_key') }}</th>
                      <th>{{ __('general_content.qty_trans_key') }}</th>
                      <th>{{ __('general_content.unit_trans_key') }}</th>
                      <th>{{ __('general_content.price_trans_key') }}</th>
                      <th>{{ __('general_content.discount_trans_key') }}</th>
                      <th>{{ __('general_content.vat_trans_key') }}</th>
                      <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                      <th></th>
                    </tr>
                    <tr>
                        <th colspan="9"></th>
                        <th colspan="2">
                          <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.new_credit_note_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                        </th>
                    </tr>
                  </tfoot>
              </table>
            </form>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Invoice->id, 'type' => 'invoice'])
      </div>
      @endif
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop