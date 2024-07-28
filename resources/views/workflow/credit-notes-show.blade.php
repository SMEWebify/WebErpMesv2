@extends('adminlte::page')

@section('title', __('general_content.credit_note_trans_key'))

@section('content_header')
  <x-Content-header-previous-button  h1="{{ __('general_content.credit_note_trans_key') }} : {{  $CreditNotes->code }}" previous="{{ $previousUrl }}" list="{{ route('credit-notes') }}" next="{{ $nextUrl }}"/>
@stop


@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link active" href="#CreditNotes" data-toggle="tab">{{ __('general_content.invoice_info_trans_key') }}</a></li>
        <li class="nav-item"><a class="nav-link" href="#CreditNotesLines" data-toggle="tab">{{ __('general_content.invoice_lines_trans_key') }}</a></li>
        </ul>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="tab-content">
        <div class="tab-pane active" id="CreditNotes">
            <div class="row">
            <div class="col-md-9">
                @include('include.alert-result')
                <form method="POST" action="{{ route('credit.notes.update', ['id' => $CreditNotes->id]) }}" enctype="multipart/form-data">
                <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                    @csrf
                    <div class="card card-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $CreditNotes->code }}
                            </div>
                            <div class="form-group col-md-4">
                                <x-adminlte-select name="statu" label="{{ __('general_content.status_trans_key') }}" label-class="text-success" igroup-size="sm">
                                    <x-slot name="prependSlot">
                                        <div class="input-group-text bg-gradient-success">
                                            <i class="fas fa-exclamation"></i>
                                        </div>
                                    </x-slot>
                                    <option value="1" @if(1 == $CreditNotes->statu ) Selected @endif >{{ __('general_content.pending_trans_key') }}</option>
                                    <option value="2" @if(2 == $CreditNotes->statu ) Selected @endif >{{ __('general_content.approved_trans_key') }}</option>
                                    <option value="3" @if(3 == $CreditNotes->statu ) Selected @endif >{{ __('general_content.rejected_trans_key') }}</option>
                                </x-adminlte-select>
                            </div>
                            <div class="form-group col-md-4">
                                @include('include.form.form-input-label',['label' => __('general_content.label_trans_key'), 'Value' =>  $CreditNotes->label])
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                            </div>
                            <div class="form-group col-md-6">
                            </div>
                        </div>
                    </div>
                    <div class="card card-body">
                        <div class="row">
                            <x-FormTextareaComment  comment="{{ $CreditNotes->reason }}" />
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
                        {{ __('general_content.created_at_trans_key') }} : {{ $CreditNotes->GetPrettyCreatedAttribute() }}
                    </div>
                    <div class="card-body">
                        {{ __('general_content.companie_name_trans_key') }} :  <x-CompanieButton id="{{ $CreditNotes->companie['id'] }}" label="{{ $CreditNotes->companie['label'] }}"  />
                    </div>
                    <div class="card-body">
                        {{ __('general_content.adress_name_trans_key') }} :   {{ $CreditNotes->adresse['label'] }} - {{ $CreditNotes->adresse['adress'] }}
                    </div>
                    <div class="card-body">
                        {{ __('general_content.contact_name_trans_key') }} :  {{ $CreditNotes->contact['first_name'] }} - {{ $CreditNotes->contact['name'] }}
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
                                {{ __('general_content.credit_note_trans_key') }}
                            </td>
                            <td>
                                <x-ButtonTextPDF route="{{ route('pdf.credit.note', ['Document' => $CreditNotes->id])}}" />
                            </td>
                        </tr>
                    </table>
                </div>
                </x-adminlte-card>
            </div>
            </div>
        </div>       
        <div class="tab-pane " id="CreditNotesLines">
            <!-- Table row -->
            <div class="row">
            <div class="col-12 table-responsive">
                @csrf
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.delivery_notes_trans_key') }}</th>
                        <th>{{ __('general_content.invoices_trans_key') }}</th>
                        <th>{{ __('general_content.external_id_trans_key') }}</th>
                        <th>{{ __('general_content.description_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.unit_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                        <th>{{ __('general_content.vat_trans_key') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                        @forelse($CreditNotes->creditNotelines as $CreditNotesLine)
                        <tr>
                            <td>
                                <x-OrderButton id="{{ $CreditNotesLine->orderLine->order['id'] }}" code="{{ $CreditNotesLine->orderLine->order['code'] }}"  />
                            </td>
                            <td>
                                @if($CreditNotesLine->invoiceLine->delivery_line_id)
                                <x-DeliveryButton id="{{ $CreditNotesLine->invoiceLine->deliveryLine->delivery['id'] }}" code="{{ $CreditNotesLine->invoiceLine->deliveryLine->delivery['code'] }}"  />
                                @else
                                {{ __('general_content.delivered_without_dn_trans_key') }}
                                @endif
                            </td>
                            <td>
                                @if($CreditNotesLine->invoice_line_id)

                                <a class="btn btn-primary btn-sm" href="{{ route('invoices.show', ['id' => $CreditNotesLine->invoiceLine->invoices_id ])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $CreditNotesLine->invoiceLine->invoice['code'] }}
                                </a>
                                @else
                                -
                                @endif
                            </td>
                            <td>{{ $CreditNotesLine->orderLine['code'] }}</td>
                            <td>{{ $CreditNotesLine->orderLine['label'] }}</td>
                            <td>{{ $CreditNotesLine->qty }}</td>
                            <td>{{ $CreditNotesLine->OrderLine->Unit['label'] }}</td>
                            <td>{{ $CreditNotesLine->unit_price }} {{ $Factory->curency }}</td>
                            <td>{{ $CreditNotesLine->OrderLine->VAT['rate'] }} %</td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="10" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_notes_trans_key') }}</th>
                            <th>{{ __('general_content.invoices_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                        </tr>
                    </tfoot>
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