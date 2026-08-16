@extends('adminlte::page')

@section('title', __('general_content.po_receipt_trans_key')   . ' - ' . $PurchaseReceipt->code)

@section('content_header')
  <x-Content-header-previous-button  h1="{{ __('general_content.po_receipt_trans_key') }}: {{  $PurchaseReceipt->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.receipt') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">{{ __('general_content.purchase_receipt_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">{{ __('general_content.purchase_receipt_lines_trans_key') }} ({{ count($PurchaseReceipt->PurchaseReceiptLines) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Documents" data-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($PurchaseReceipt->files) }})</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            @if($PurchaseReceipt->companie->recept_controle == 1 && $PurchaseReceipt->reception_controlled == 0)
            <x-adminlte-alert theme="warning" title="Warning">
              {{ __('general_content.po_receipt_note_trans_key') }}
              <form action="{{ route('purchase.receipts.reception_control', $PurchaseReceipt->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('general_content.validate_control_trans_key') }}</button>
              </form>
            </x-adminlte-alert>
            @endif
            <form method="POST" action="{{ route('receipt.update', ['id' => $PurchaseReceipt->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="orange" theme-mode="outline" maximizable>
                @csrf
                    <div class="row">
                      <div class="form-group col-md-6">
                        <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $PurchaseReceipt->code }}</p>
                        <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $PurchaseReceipt->GetshortCreatedAttribute() }}</p>
                      </div>
                      <div class="form-group col-md-6">
                        <x-adminlte-select name="statu" label="{{ __('general_content.status_trans_key') }}" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseReceipt->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                          <option value="2" @if(2 == $PurchaseReceipt->statu ) Selected @endif >{{ __('general_content.stock_trans_key') }}</option>
                        </x-adminlte-select>
                      </div>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-6">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_purchase_reciept_trans_key'), 'Value' =>  $PurchaseReceipt->label])
                      </div>

                      <div class="form-group col-md-6">
                        <x-adminlte-input name="delivery_note_number" label="{{ __('general_content.delivery_note_number_trans_key') }}" placeholder="{{ __('general_content.delivery_note_number_trans_key') }}" value="{{  $PurchaseReceipt->delivery_note_number }}" label-class="text-success">
                          <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-tags"></i>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                    </div>
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseReceipt->comment }}" />
                    </div>
                  <div class="modal-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" theme-mode="outline" maximizable>
              <div class="card-body">
                {{ __('general_content.created_at_trans_key') }} :  {{ $PurchaseReceipt->GetPrettyCreatedAttribute() }}
              </div>
              <div class="card-body">
                {{ __('general_content.companie_name_trans_key') }} :  <x-CompanieButton id="{{ $PurchaseReceipt->companie['id'] }}" label="{{ $PurchaseReceipt->companie['label'] }}"  />
              </div>
              <div class="card-body">
                {{ __('general_content.delevery_time_trans_key') }} :  {{ $averageReceptionDelay }}
              </div>
              @if($PurchaseReceipt->companie->recept_controle == 1 && $PurchaseReceipt->reception_controlled == 1)
              <div class="card-body">
                {{ __('general_content.reception_control_trans_key') }} :  {{ $PurchaseReceipt->UserReceptionControl['name'] }} - {{ $PurchaseReceipt->GetPrettyControlDateAttribute() }}
              </div>
              @endif
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="secondary" theme-mode="outline" collapsible="collapsed" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                      <td style="width:50%">
                        {{ __('general_content.po_receipt_trans_key') }}
                      </td>
                      <td>
                        <x-ButtonTextPDF route="{{ route('pdf.receipt', ['Document' => $PurchaseReceipt->id])}}" />
                      </td>
                  </tr>
                </table>
              </div>
            </x-adminlte-card>

          </div>
        </div>
      </div>
      <div class="tab-pane" id="PurchaseLines">
        <div
          id="purchase-receipt-lines-page-app"
          data-receipt-id="{{ $PurchaseReceipt->id }}"
          data-receipt-code="{{ $PurchaseReceipt->code }}"
          data-receipt-statu="{{ $PurchaseReceipt->statu }}"
          data-user-id="{{ Auth::id() }}"
          data-endpoints="{{ json_encode($reactEndpoints) }}"
        ></div>
      </div>
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'purchase-receipt',
          'fileableId'   => $PurchaseReceipt->id,
        ])
      </div>
    </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
