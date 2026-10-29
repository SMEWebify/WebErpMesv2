@extends('adminlte::page')

@section('title', __('general_content.purchase_trans_key')   . ' - ' . $Purchase->code)

@php
$purchaseSteps = json_encode([
    ['value' => 1, 'label' => __('general_content.in_progress_trans_key')],
    ['value' => 2, 'label' => __('general_content.ordered_trans_key')],
    ['value' => 3, 'label' => __('general_content.partly_received_trans_key')],
    ['value' => 4, 'label' => __('general_content.rceived_trans_key')],
]);
@endphp

@section('content_header')
  <x-document-header h1="{{  __('general_content.purchase_trans_key') }} : {{  $Purchase->code }}"
                     previous="{{ $previousUrl }}" list="{{ route('purchases') }}" next="{{ $nextUrl }}"
                     :steps="$purchaseSteps" statu="{{ $Purchase->statu }}"
                     endpoint="{{ route('purchases.json.statu', $Purchase->id) }}"
                     redirect="{{ route('purchases.show', $Purchase->id) }}"/>
@stop

@section('right-sidebar')

@section('content')


<div class="card mb-3">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-bs-toggle="tab">{{  __('general_content.purchase_info_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-bs-toggle="tab">{{  __('general_content.purchase_lines_trans_key') }} ({{ count($Purchase->PurchaseLines) }})</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-bs-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#Documents" data-bs-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($Purchase->files) }})</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('purchase.update', ['id' => $Purchase->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="orange" theme-mode="outline" maximizable>
                @csrf 
                <div class="row">
                  <div class="mb-3 col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Purchase->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Purchase->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="mb-3 col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.name_purchase_trans_key'), 'Value' =>  $Purchase->label])
                  </div>
                </div>
                <div class="row">
                  <label for="InputWebSite">{{ __('general_content.supplier_info_trans_key') }}</label>
                </div>
                @if( $Purchase->companies_contacts_id == 0 & $Purchase->companies_addresses_id ==0)
                <x-adminlte-alert theme="info" title="Info">{{  __('general_content.update_valide_trans_key') }}</x-adminlte-alert>
                @endif
                <div class="row">
                  <div class="mb-3 col-md-6">
                    @include('include.form.form-select-companie',['companiesId' =>  $Purchase->companies_id])
                  </div>
                  <div class="mb-3 col-md-6">
                    
                  </div>
                </div>
                <div class="row">
                  <div class="mb-3 col-md-6">
                    @include('include.form.form-select-adress',['adressId' =>   $Purchase->companies_addresses_id])
                  </div>
                  <div class="mb-3 col-md-6">
                    @include('include.form.form-select-contact',['contactId' =>   $Purchase->companies_contacts_id])
                  </div>
                </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Purchase->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" theme-mode="outline" maximizable>
              @include('include.sub-total-price')
            </x-adminlte-card>

            @if ($supplierQualification)
              @include('include.supplier-qualification-card', ['qualification' => $supplierQualification])
            @endif

            <x-adminlte-card title="{{ __('purchase_quality.order_card') }} ({{ $qualityClauses->count() }})" theme="teal" theme-mode="outline" icon="fas fa-shield-alt" :collapsible="$qualityClauses->isEmpty() ? 'collapsed' : 'expanded'">
              @if ($Purchase->quality_clauses_frozen_at)
                <p class="small text-body-secondary mb-2"><i class="fas fa-lock me-1"></i>{{ __('purchase_quality.order_frozen', ['date' => $Purchase->quality_clauses_frozen_at->format('d/m/Y H:i')]) }}</p>
              @else
                <p class="small text-body-secondary mb-2">{{ __('purchase_quality.order_draft') }}</p>
              @endif
              @forelse ($qualityClauses as $clause)
                <div class="mb-2">
                  <code>{{ $clause->code }}</code> <strong>{{ $clause->label }}</strong>
                  <div class="small" style="white-space: pre-line;">{{ $clause->text() }}</div>
                </div>
              @empty
                <p class="small mb-0">{{ __('purchase_quality.order_none') }}</p>
              @endforelse
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="secondary" theme-mode="outline" collapsible="collapsed" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                      <td style="width:50%">{{ __('general_content.purchase_trans_key') }}</td>
                      <td>
                        @if( $Purchase->companies_contacts_id != 0 & $Purchase->companies_addresses_id !=0)
                        <x-ButtonTextPDF route="{{ route('pdf.purchase', ['Document' => $Purchase->id])}}" />
                        @else
                        {{  __('general_content.update_valide_trans_key') }}
                        @endif
                      </td>
                  </tr>
                  @if(config('mail.default') && config('mail.from.address'))
                  <tr>
                    <td style="width:50%">{{ __('general_content.email_trans_key') }}</td>
                    <td><x-ButtonTextEmail route="{{ route('email.create', ['type' => 'purchase', 'id' => $Purchase->id]) }}" /></td>
                  </tr>
                  @endif
                </table>
              </div>
              <hr>
              <div class="card-body">
                @if($Purchase->Rating->isEmpty())
                <form action="{{ route('companies.ratings.store') }}" method="POST">
                  @csrf
                  <input type="hidden" name="purchases_id" value="{{ $Purchase->id }}" >
                  <input type="hidden" name="companies_id" value="{{ $Purchase->companies_id }}" >
                  <div class="mb-3">
                    <label for="rating">{{ __('general_content.supplier_rate_trans_key') }}</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="fas fa-star-half-alt"></i></span>
                      <select name="rating" id="rating" class="form-select">
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                          <option value="4">4</option>
                          <option value="5">5</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-3">
                    <x-FormTextareaComment  comment="" />
                  </div>
                  <x-adminlte-button class="rounded-0" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </form>
                @else
                  @php
                      $Rating = $Purchase->Rating->toArray();
                  @endphp
                  <label for="rating">{{ __('general_content.supplier_rate_trans_key') }}</label>
                  @for ($i = 1; $i <= 5; $i++)
                      @if ($i <= $Rating[0]['rating'])
                          <span class="badge text-bg-warning">&#9733;</span>
                      @else
                          <span class="badge text-bg-info">&#9734;</span>
                      @endif
                  @endfor
                @endif
              </div>
            </x-adminlte-card>

            @include('include.email-list', ['mailsList'=> $Purchase->emailLogs,])
          </div>
        </div>
      </div>    
      <div class="tab-pane " id="PurchaseLines">
          <div
              id="purchase-lines-page-app"
              data-purchase-id="{{ $Purchase->id }}"
              data-purchase-statu="{{ $Purchase->statu }}"
              data-endpoints="{{ json_encode([
                  'lines'         => route('purchases.lines.json.for-purchase', ['purchaseId' => $Purchase->id]),
                  'selectData'    => route('purchases.lines.json.select-data',  ['purchaseId' => $Purchase->id]),
                  'productSearch' => route('products.json.search', array_filter(['supplier_id' => $Purchase->companies_id])),
                  'store'         => route('purchases.lines.json.store',         ['purchaseId' => $Purchase->id]),
                  'update'        => route('purchases.lines.json.update',        ['purchaseId' => $Purchase->id, 'id' => '__ID__']),
                  'destroy'       => route('purchases.lines.json.destroy',       ['purchaseId' => $Purchase->id, 'id' => '__ID__']),
                  'duplicate'     => route('purchases.lines.json.duplicate',     ['purchaseId' => $Purchase->id, 'id' => '__ID__']),
                  'reorder'       => route('purchases.lines.json.reorder',       ['purchaseId' => $Purchase->id]),
                  'storeReceipt'  => route('purchases.lines.json.store-receipt', ['purchaseId' => $Purchase->id]),
              ]) }}"
          ></div>
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Purchase->id, 'type' => 'purchase'])
      </div>
      @endif
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'purchase',
          'fileableId'   => $Purchase->id,
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
@stop