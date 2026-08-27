@extends('adminlte::page')

@section('title', __('general_content.invoices_trans_key') . ' - ' . $Invoice->code)

@php
$invoiceSteps = [
    ['value' => 1, 'label' => __('general_content.in_progress_trans_key')],
    ['value' => 2, 'label' => __('general_content.send_trans_key')],
    ['value' => 3, 'label' => __('general_content.pending_trans_key')],
    ['value' => 4, 'label' => __('general_content.unpaid_trans_key')],
    ['value' => 5, 'label' => __('general_content.paid_trans_key')],
];
@endphp

@section('content_header')
  <x-document-header h1="{{ __('general_content.invoices_trans_key') }} : {{  $Invoice->code }}"
                     previous="{{ $previousUrl }}" list="{{ route('invoices') }}" next="{{ $nextUrl }}"
                     :steps="$invoiceSteps" statu="{{ $Invoice->statu }}"
                     endpoint="{{ route('invoices.json.statu', $Invoice->id) }}"
                     redirect="{{ route('invoices.show', $Invoice->id) }}"/>
@stop


@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Invoice" data-toggle="tab">{{ __('general_content.invoice_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#InvoiceLines" data-toggle="tab">{{ __('general_content.invoice_lines_trans_key') }} ({{ count($Invoice->InvoiceLines) }})</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }}  ({{ count($CustomFields) }})</a></li>
      @endif
      <li class="nav-item">
        <a class="nav-link" href="#Payments" data-toggle="tab">
          <i class="fas fa-euro-sign mr-1"></i>Règlements
          @if($Invoice->paid_amount > 0)
            <span class="badge badge-{{ $Invoice->remaining_amount <= 0 ? 'success' : 'warning' }} ml-1">
              {{ $Invoice->remaining_amount <= 0 ? 'Soldée' : 'Partiel' }}
            </span>
          @endif
        </a>
      </li>
      <li class="nav-item"><a class="nav-link" href="#Documents" data-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($Invoice->files) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Logs" data-toggle="tab">Logs</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Invoice">
        <x-relational-breadcrumb :entity="$Invoice" />
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('invoices.update', ['id' => $Invoice->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="danger" theme-mode="outline" maximizable>
                @csrf
                <div class="row">
                  <div class="form-group col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Invoice->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Invoice->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' => __('general_content.label_trans_key'), 'Value' =>  $Invoice->label])
                    @include('include.form.form-input-customerInfo',['customerReference' =>  $Invoice->customer_reference])
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
                <div class="row">
                  <div class="form-group col-md-4">
                    <x-adminlte-select2 name="companies_id" id="invoice_company_id" label="{{ __('general_content.companie_name_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.companie_name_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-building"></i>
                            </div>
                        </x-slot>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $company->id == $Invoice->companies_id ? 'selected' : '' }}>{{ $company->label }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                  <div class="form-group col-md-4">
                    <x-adminlte-select2 name="companies_addresses_id" id="invoice_address_id" label="{{ __('general_content.adress_name_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.adress_name_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                        </x-slot>
                        @foreach($addresses as $address)
                            <option value="{{ $address->id }}" {{ $address->id == $Invoice->companies_addresses_id ? 'selected' : '' }}>{{ $address->label }}{{ $address->adress ? ' - ' . $address->adress : '' }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                  <div class="form-group col-md-4">
                    <x-adminlte-select2 name="companies_contacts_id" id="invoice_contact_id" label="{{ __('general_content.contact_name_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.contact_name_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-user"></i>
                            </div>
                        </x-slot>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" {{ $contact->id == $Invoice->companies_contacts_id ? 'selected' : '' }}>{{ $contact->first_name }} {{ $contact->name }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Invoice->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>

          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" theme-mode="outline" maximizable>
              <div class="card-body">
                @include('include.sub-total-price')
              </div>
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="secondary" theme-mode="outline" collapsible maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                    <tr>
                        <td style="width:50%">{{ __('general_content.invoices_trans_key') }}</td>
                        <td>
                            @if($Invoice->statu === 1)
                                <span class="badge badge-secondary">{{ __('general_content.invoice_draft_trans_key') }} - PDF indisponible</span>
                            @else
                                <x-ButtonTextPDF route="{{ route('pdf.invoice', ['Document' => $Invoice->id])}}" />
                            @endif
                        </td>
                    </tr>
                    @if(config('mail.default') && config('mail.from.address'))
                    <tr>
                      <td style="width:50%">{{ __('general_content.email_trans_key') }}</td>
                      <td>
                        @if($Invoice->statu === 1)
                            <span class="badge badge-secondary">{{ __('general_content.invoice_draft_trans_key') }} - {{ __('general_content.invoice_draft_no_email_short_trans_key') }}</span>
                        @else
                            <x-ButtonTextEmail route="{{ route('email.create', ['type' => 'invoice', 'id' => $Invoice->id]) }}" />
                        @endif
                      </td>
                    </tr>
                    @endif
                </table>
              </div>
            </x-adminlte-card>

            @if($pdpEnabled && $Invoice->invoice_type === 1)
            @include('integrations.partials.pdp-invoice-card', ['Invoice' => $Invoice, 'submission' => $pdpSubmission])
            @endif

            @include('include.email-list', ['mailsList'=> $Invoice->emailLogs,])
          </div>
        </div>
      </div>       
      <div class="tab-pane " id="InvoiceLines">
        @php
          $invoiceDataService = app(\App\Services\InvoiceDataService::class);
          $invoiceLinesData = $Invoice->InvoiceLines->map(fn ($l) => $invoiceDataService->formatDraftLine($l));
          $linesEndpoints = [
              'updateLine' => route('invoices.lines.update', [$Invoice->id, '__LINE_ID__']),
              'storeLine'  => route('invoices.lines.store', $Invoice->id),
              'deleteLine' => route('invoices.lines.destroy', [$Invoice->id, '__LINE_ID__']),
              'emit'       => route('invoices.emit', $Invoice->id),
          ];
          $linesTrans = [
              'order'       => __('general_content.order_trans_key'),
              'delivery'    => __('general_content.delivery_notes_trans_key'),
              'ref'         => __('general_content.external_id_trans_key'),
              'description' => __('general_content.description_trans_key'),
              'qty'         => __('general_content.qty_trans_key'),
              'unit'        => __('general_content.unit_trans_key'),
              'price'       => __('general_content.price_trans_key'),
              'discount'    => __('general_content.discount_trans_key'),
              'vat'         => __('general_content.vat_trans_key'),
              'total'       => 'Montant HT',
              'status'      => __('general_content.invoice_status_trans_key'),
              'in_progress' => __('general_content.in_progress_trans_key'),
              'send'        => __('general_content.send_trans_key'),
              'pending'     => __('general_content.pending_trans_key'),
              'unpaid'      => __('general_content.unpaid_trans_key'),
              'paid'        => __('general_content.paid_trans_key'),
          ];
        @endphp
        <div
          id="invoice-lines-draft-app"
          data-invoice-id="{{ $Invoice->id }}"
          data-statu="{{ $Invoice->statu }}"
          data-lines="{{ json_encode($invoiceLinesData) }}"
          data-endpoints="{{ json_encode($linesEndpoints) }}"
          data-currency="{{ app('Factory')->curency ?? 'EUR' }}"
          data-vats="{{ json_encode($vats) }}"
          data-units="{{ json_encode($units) }}"
          data-trans="{{ json_encode($linesTrans) }}"
        ></div>

        @if($Invoice->statu >= 2)
        <div class="mt-3">
          <form action="{{ route('credit-notes.store.from.invoice') }}" method="POST">
            @csrf
            @foreach($Invoice->InvoiceLines as $line)
              <input type="hidden" name="all_invoice_lines[]" value="{{ $line->id }}">
            @endforeach
            <div class="d-flex align-items-center">
              <label class="mr-2 mb-0 text-muted small">Sélectionner les lignes pour avoir :</label>
              @foreach($Invoice->InvoiceLines as $line)
                <label class="mr-3 mb-0 small">
                  <input type="checkbox" name="selected_invoice_lines[]" value="{{ $line->id }}" class="mr-1">
                  {{ $line->display_code ?: $line->display_label }}
                </label>
              @endforeach
              <button type="submit" class="btn btn-info btn-sm ml-auto">
                <i class="fas fa-save mr-1"></i>{{ __('general_content.new_credit_note_trans_key') }}
              </button>
            </div>
          </form>
        </div>
        @endif
      </div>
      <div class="tab-pane" id="Payments">
        <div
          id="invoice-payments-tab-app"
          data-invoice-id="{{ $Invoice->id }}"
          data-endpoints="{{ json_encode($paymentEndpoints) }}"
          data-payment-methods="{{ json_encode($paymentMethods) }}"
        ></div>
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Invoice->id, 'type' => 'invoice'])
      </div>
      <div class="tab-pane " id="Logs">
        @include('include.logs-viewer-mount', ['logsSubjectType' => 'App\Models\Workflow\Invoices', 'logsSubjectId' => $Invoice->id])
      </div>
      @endif
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'invoice',
          'fileableId'   => $Invoice->id,
        ])
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
<script>
(function ($) {
    var acUrl = '{{ $companyAcUrl }}';

    $('#invoice_company_id').on('change', function () {
        var companyId = $(this).val();
        $.getJSON(acUrl, { company_id: companyId }, function (data) {
            var $addr = $('#invoice_address_id').empty();
            $.each(data.addresses, function (_, a) {
                $addr.append($('<option>', { value: a.id, text: a.label }));
            });
            $addr.trigger('change');

            var $contact = $('#invoice_contact_id').empty();
            $.each(data.contacts, function (_, c) {
                $contact.append($('<option>', { value: c.id, text: c.label }));
            });
            $contact.trigger('change');
        });
    });
}(jQuery));
</script>
@stop