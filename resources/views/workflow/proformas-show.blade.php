@extends('adminlte::page')

@section('title', 'Proforma - ' . $Proforma->code)

@php
  $steps = [
    ['value' => 1, 'label' => 'Brouillon'],
    ['value' => 2, 'label' => 'Envoyée'],
    ['value' => 3, 'label' => 'Acceptée'],
    ['value' => 4, 'label' => 'Refusée'],
    ['value' => 5, 'label' => 'Convertie'],
  ];
@endphp

@section('content_header')
  <x-document-header h1="{{ $Proforma->code }}" badge="PROFORMA"
                     list="{{ route('proformas') }}"
                     :steps="$steps" statu="{{ $Proforma->statu }}"
                     endpoint="{{ route('proformas.json.statu', $Proforma->id) }}"
                     redirect="{{ route('proformas.show', $Proforma->id) }}"/>
@stop

@section('content')


<div class="card mb-3">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Info" data-bs-toggle="tab">Informations</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-bs-toggle="tab">Lignes ({{ count($Proforma->invoiceLines) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Documents" data-bs-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($Proforma->files) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Logs" data-bs-toggle="tab">Logs</a></li>
    </ul>
  </div>

  <div class="card-body">
    <div class="tab-content">

      {{-- Onglet infos --}}
      <div class="tab-pane active" id="Info">
        <x-relational-breadcrumb :entity="$Proforma" />

        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('proformas.update', ['id' => $Proforma->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="Informations" theme="info" theme-mode="outline" maximizable>
                @csrf
                <div class="row">
                  <div class="mb-3 col-md-6">
                    <p><label class="text-success">Code</label>  {{ $Proforma->code }}</p>
                    <p><label class="text-success">Date</label>  {{ $Proforma->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="mb-3 col-md-6">
                    @include('include.form.form-input-label', ['label' => 'Libellé', 'Value' => $Proforma->label])
                    @include('include.form.form-input-customerInfo', ['customerReference' => $Proforma->customer_reference])
                  </div>
                </div>
                <div class="row">
                  <div class="mb-3 col-md-6">
                    <label>Échéance</label>
                    <input type="date" class="form-control" name="due_date" value="{{ $Proforma->due_date }}">
                  </div>
                  <div class="mb-3 col-md-6">
                    <label>Incoterm</label>
                    <input type="text" class="form-control" name="incoterm" value="{{ $Proforma->incoterm }}">
                  </div>
                </div>
                <div class="row">
                  <div class="mb-3 col-md-4">
                    <x-adminlte-select-bs name="companies_id" id="proforma_company_id" label="Client" label-class="text-info" igroup-size="s">
                      <x-slot name="prependSlot"><div class="input-group-text text-bg-info bg-gradient"><i class="fas fa-building"></i></div></x-slot>
                      @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ $company->id == $Proforma->companies_id ? 'selected' : '' }}>{{ $company->label }}</option>
                      @endforeach
                    </x-adminlte-select-bs>
                  </div>
                  <div class="mb-3 col-md-4">
                    <x-adminlte-select-bs name="companies_addresses_id" id="proforma_address_id" label="Adresse" label-class="text-info" igroup-size="s">
                      <x-slot name="prependSlot"><div class="input-group-text text-bg-info bg-gradient"><i class="fas fa-map-marked-alt"></i></div></x-slot>
                      @foreach($addresses as $address)
                        <option value="{{ $address->id }}" {{ $address->id == $Proforma->companies_addresses_id ? 'selected' : '' }}>{{ $address->label }}</option>
                      @endforeach
                    </x-adminlte-select-bs>
                  </div>
                  <div class="mb-3 col-md-4">
                    <x-adminlte-select-bs name="companies_contacts_id" id="proforma_contact_id" label="Contact" label-class="text-info" igroup-size="s">
                      <x-slot name="prependSlot"><div class="input-group-text text-bg-info bg-gradient"><i class="fas fa-user"></i></div></x-slot>
                      @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ $contact->id == $Proforma->companies_contacts_id ? 'selected' : '' }}>{{ $contact->first_name }} {{ $contact->name }}</option>
                      @endforeach
                    </x-adminlte-select-bs>
                  </div>
                </div>
                <div class="row">
                  <x-FormTextareaComment comment="{{ $Proforma->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="rounded-0" type="submit" label="Mettre à jour" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>

          <div class="col-md-3">
            <x-adminlte-card title="Montants" theme="secondary" theme-mode="outline" maximizable>
              <div class="card-body">
                @include('include.sub-total-price')
              </div>
            </x-adminlte-card>

            <x-adminlte-card title="Actions" theme="secondary" theme-mode="outline" collapsible maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                    <td>PDF Proforma</td>
                    <td><x-ButtonTextPDF route="{{ route('pdf.proforma', ['Document' => $Proforma->id]) }}" /></td>
                  </tr>
                  @if(isset($linkedOrder) && $linkedOrder?->statu === 0)
                  <tr>
                    <td>
                      Commande liée
                      <span class="badge text-bg-secondary ms-1">En attente paiement</span>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-warning" id="btn-activate-order">
                        <i class="fas fa-check-circle"></i> Paiement reçu
                      </button>
                    </td>
                  </tr>
                  @elseif(isset($linkedOrder) && $linkedOrder)
                  <tr>
                    <td>Commande liée</td>
                    <td><x-OrderButton id="{{ $linkedOrder->id }}" code="{{ $linkedOrder->code }}" /></td>
                  </tr>
                  @endif
                  @if(in_array($Proforma->statu, [1,2,3]))
                  <tr>
                    <td>Convertir en facture</td>
                    <td>
                      <button class="btn btn-sm btn-success" id="btn-convert-proforma">
                        <i class="fas fa-file-invoice"></i> Convertir
                      </button>
                    </td>
                  </tr>
                  @endif
                </table>
              </div>
            </x-adminlte-card>

            @include('include.email-list', ['mailsList' => $Proforma->emailLogs])
          </div>
        </div>
      </div>

      {{-- Onglet lignes --}}
      <div class="tab-pane" id="Lines">
        <div class="row">
          <div class="col-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Commande</th>
                  <th>Code</th>
                  <th>Désignation</th>
                  <th>Qté</th>
                  <th>Unité</th>
                  <th>PU HT</th>
                  <th>Remise</th>
                  <th>TVA</th>
                </tr>
              </thead>
              <tbody>
                @forelse($Proforma->invoiceLines as $line)
                <tr>
                  <td>
                    @if($line->orderLine?->order)
                      <x-OrderButton id="{{ $line->orderLine->order->id }}" code="{{ $line->orderLine->order->code }}" />
                    @endif
                  </td>
                  <td>{{ $line->display_code }}@if(!empty($line->display_ind)) <span class="badge text-bg-light" title="{{ __('general_content.plan_index_trans_key') }}">{{ __('general_content.plan_index_short_trans_key') }} {{ $line->display_ind }}</span>@endif</td>
                  <td>{{ $line->display_label }}</td>
                  <td>{{ format_qty($line->qty) }}</td>
                  <td>{{ $line->display_unit_label }}</td>
                  <td>{{ $line->formatted_selling_price }}</td>
                  <td>{{ $line->resolved_discount }} %</td>
                  <td>{{ $line->resolved_vat_rate }} %</td>
                </tr>
                @empty
                  <x-EmptyDataLine col="8" text="Aucune ligne" />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Onglet logs --}}
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'invoice',
          'fileableId'   => $Proforma->id,
        ])
      </div>
      <div class="tab-pane" id="Logs">
        @include('include.logs-viewer-mount', ['logsSubjectType' => 'App\Models\Workflow\Invoices', 'logsSubjectId' => $Proforma->id])
      </div>

    </div>
  </div>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@include('include.form.company-address-contact-sync', [
    'url' => $companyAcUrl,
    'companyId' => 'proforma_company_id',
    'addressId' => 'proforma_address_id',
    'contactId' => 'proforma_contact_id',
])
<script>
(function () {
    function post(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': @json(csrf_token()),
            },
        }).then(function (response) {
            if (!response.ok) throw new Error(response.status);
            return response.json();
        });
    }

    var activate = document.getElementById('btn-activate-order');
    if (activate) {
        activate.addEventListener('click', function () {
            if (!confirm('Confirmer la réception du paiement ? La commande sera activée et entrera en production.')) return;
            post(@json(route('proformas.activate.order', $Proforma->id)))
                .then(function () { window.location.reload(); })
                .catch(function () { alert("Erreur lors de l'activation."); });
        });
    }

    var convert = document.getElementById('btn-convert-proforma');
    if (convert) {
        convert.addEventListener('click', function () {
            if (!confirm('Convertir cette proforma en facture réelle ? Un nouveau numéro de facture lui sera attribué.')) return;
            post(@json(route('proformas.convert', $Proforma->id)))
                .then(function (res) { window.location.href = res.redirect; })
                .catch(function () { alert('Erreur lors de la conversion.'); });
        });
    }
})();
</script>
@stop
