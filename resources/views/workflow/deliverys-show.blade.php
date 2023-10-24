@extends('adminlte::page')

@section('title', __('general_content.delivery_notes_trans_key'))

@section('content_header')
  <x-Content-header-previous-button  h1="{{ __('general_content.delivery_notes_trans_key') }} : {{  $Delivery->code }}" previous="{{ $previousUrl }}" list="{{ route('deliverys') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Delivery" data-toggle="tab">{{ __('general_content.delivery_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#DeliveryLines" data-toggle="tab">{{ __('general_content.delivery_lines_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Delivery">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <form method="POST" action="{{ route('deliverys.update', ['id' => $Delivery->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-4">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Delivery->code }}
                      </div>
                      <div class="col-4">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $Delivery->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                          <option value="2" @if(2 == $Delivery->statu ) Selected @endif >{{ __('general_content.send_trans_key') }}</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-4">
                        @include('include.form.form-input-label',['label' =>'Name of delivery', 'Value' =>  $Delivery->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Delivery->comment }}" />
                    </div>
                  </div>
                  <div class="card-footer">
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
              <div class="card-body">
                <div class="table-responsive">
                  <div class="card-body">
                    {{ __('general_content.created_at_trans_key') }} :  {{ $Delivery->GetPrettyCreatedAttribute() }}
                  </div>
                  <div class="card-body">
                    {{ __('general_content.companie_name_trans_key') }} :  <x-CompanieButton id="{{ $Delivery->companie['id'] }}" label="{{ $Delivery->companie['label'] }}"  />
                  </div>
                  <div class="card-body">
                    {{ __('general_content.adress_name_trans_key') }} :   {{ $Delivery->adresse['label'] }} - {{ $Delivery->adresse['adress'] }}
                  </div>
                  <div class="card-body">
                    {{ __('general_content.contact_name_trans_key') }} :  {{ $Delivery->contact['first_name'] }} - {{ $Delivery->contact['name'] }}
                  </div>
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
                          {{ __('general_content.delivery_notes_trans_key') }}
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.delivery', ['Document' => $Delivery->id])}}" />
                        </td>
                    </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>      
      <div class="tab-pane " id="DeliveryLines">
        <!-- Table row -->
        <div class="row">
          <div class="col-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>{{ __('general_content.order_trans_key') }}</th>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th>{{ __('general_content.qty_trans_key') }}</th>
                  <th>{{ __('general_content.unit_trans_key') }}</th>
                  <th>{{ __('general_content.delivered_qty_trans_key') }}</th>
                  <th>{{ __('general_content.remaining_qty_trans_key') }}</th>
                  <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($Delivery->DeliveryLines as $DeliveryLine)
                  <tr>
                    <td>
                      <x-OrderButton id="{{ $DeliveryLine->OrderLine->order['id'] }}" code="{{ $DeliveryLine->OrderLine->order['code'] }}"  />
                    </td>
                    <td>{{ $DeliveryLine->OrderLine['code'] }}</td>
                    <td>{{ $DeliveryLine->OrderLine['label'] }}</td>
                    <td>{{ $DeliveryLine->OrderLine['qty'] }}</td>
                    <td>{{ $DeliveryLine->OrderLine->Unit['label'] }}</td>
                    <td>{{ $DeliveryLine->qty }}</td>
                    <td>{{ $DeliveryLine->OrderLine['delivered_remaining_qty'] }}</td>
                    <td>
                      @if(1 == $DeliveryLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.chargeable_trans_key') }}</span>@endif
                      @if(2 == $DeliveryLine->invoice_status )  <span class="badge badge-danger">{{ __('general_content.not_chargeable_trans_key') }}</span>@endif
                      @if(3 == $DeliveryLine->invoice_status )  <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>@endif
                      @if(4 == $DeliveryLine->invoice_status )  <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>@endif
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.unit_trans_key') }}</th>
                    <th>{{ __('general_content.delivered_qty_trans_key') }}</th>
                    <th>{{ __('general_content.remaining_qty_trans_key') }}</th>
                    <th>{{ __('general_content.invoice_status_trans_key') }}</th>
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