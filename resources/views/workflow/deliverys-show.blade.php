@extends('adminlte::page')

@section('title', __('general_content.delivery_notes_trans_key')  . ' - ' . $Delivery->code)

@php
$deliverySteps = [
    ['value' => 1, 'label' => __('general_content.in_progress_trans_key')],
    ['value' => 2, 'label' => __('general_content.send_trans_key')],
];
@endphp

@section('content_header')
  <x-document-header h1="{{ __('general_content.delivery_notes_trans_key') }} : {{  $Delivery->code }}"
                     previous="{{ $previousUrl }}" list="{{ route('deliverys') }}" next="{{ $nextUrl }}"
                     :steps="$deliverySteps" statu="{{ $Delivery->statu }}"
                     endpoint="{{ route('deliverys.json.statu', $Delivery->id) }}"
                     redirect="{{ route('deliverys.show', $Delivery->id) }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Delivery" data-toggle="tab">{{ __('general_content.delivery_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#DeliveryLines" data-toggle="tab">{{ __('general_content.delivery_lines_trans_key') }} ({{ count($Delivery->DeliveryLines) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Photos" data-toggle="tab">{{ __('general_content.photos_trans_key') }} ({{ count($Delivery->photos) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Packaging" data-toggle="tab">{{ __('general_content.packaging_trans_key') }} ({{ count($Delivery->packaging) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Returns" data-toggle="tab">{{ __('general_content.returns_trans_key') }} ({{ count($Delivery->returns) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#NonConformities" data-toggle="tab">{{ __('general_content.non_conformities_trans_key') }} ({{ count($Delivery->QualityNonConformity) }})</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#Documents" data-toggle="tab"><i class="far fa-folder-open"></i> {{ __('general_content.documents_trans_key') }} ({{ count($Delivery->files) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Logs" data-toggle="tab">Logs</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Delivery">
        <x-relational-breadcrumb :entity="$Delivery" />
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('deliverys.update', ['id' => $Delivery->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf
                <div class="row">
                  <div class="form-group col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Delivery->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Delivery->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.label_trans_key'), 'Value' =>  $Delivery->label])
                    @include('include.form.form-input-customerInfo',['customerReference' =>  $Delivery->customer_reference])
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-4">
                    <x-adminlte-select2 name="companies_id" id="delivery_company_id" label="{{ __('general_content.companie_name_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.companie_name_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-building"></i>
                            </div>
                        </x-slot>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ $company->id == $Delivery->companies_id ? 'selected' : '' }}>{{ $company->label }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                  <div class="form-group col-md-4">
                    <x-adminlte-select2 name="companies_addresses_id" id="delivery_address_id" label="{{ __('general_content.adress_name_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.adress_name_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                        </x-slot>
                        @foreach($addresses as $address)
                            <option value="{{ $address->id }}" {{ $address->id == $Delivery->companies_addresses_id ? 'selected' : '' }}>{{ $address->label }}{{ $address->adress ? ' - ' . $address->adress : '' }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                  <div class="form-group col-md-4">
                    <x-adminlte-select2 name="companies_contacts_id" id="delivery_contact_id" label="{{ __('general_content.contact_name_trans_key') }}" label-class="text-info"
                        igroup-size="s" data-placeholder="{{ __('general_content.contact_name_trans_key') }}">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-info">
                                <i class="fas fa-user"></i>
                            </div>
                        </x-slot>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" {{ $contact->id == $Delivery->companies_contacts_id ? 'selected' : '' }}>{{ $contact->first_name }} {{ $contact->name }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <x-adminlte-select2 name="purchases_id" id="purchases_id" label="{{ __('general_content.name_purchase_trans_key') }}" label-class="text-secondary"
                      igroup-size="s" data-placeholder="{{ __('general_content.name_purchase_trans_key') }}">
                      <x-slot name="prependSlot">
                          <div class="input-group-text bg-gradient-secondary">
                              <i class="fas fa-list"></i>
                          </div>
                      </x-slot>
                      <option value="">{{ __('general_content.select_purchase_order_trans_key') }}</option>
                        @foreach ($PruchasesSelect as $item)
                        <option value="{{ $item->id }}" @if($item->id == $Delivery->purchases_id) Selected @endif>{{ $item->code }}</option>
                        @endforeach
                    </x-adminlte-select2>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="tracking_number">{{ __('general_content.tracking_number_note_trans_key') }}</label>
                    <input type="text" class="form-control" name="tracking_number"  id="tracking_number" value="{{  $Delivery->tracking_number }}">
                  </div>
                </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Delivery->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                    <tr>
                        <td style="width:50%">{{ __('general_content.delivery_notes_trans_key') }}</td>
                        <td><x-ButtonTextPDF route="{{ route('pdf.delivery', ['Document' => $Delivery->id])}}" /></td>
                    </tr>
                    @if(config('mail.default') && config('mail.from.address'))
                    <tr>
                      <td style="width:50%">{{ __('general_content.email_trans_key') }}</td>
                      <td><x-ButtonTextEmail route="{{ route('email.create', ['type' => 'delivery', 'id' => $Delivery->id]) }}" /></td>
                    </tr>
                    @endif
                    @if($Delivery->uuid)
                    <tr>
                      <td style="width:50%">{{ __('general_content.public_link_trans_key') }}</td>
                      <td>
                        <button class="btn btn-info btn-sm" onclick="copyToClipboard('{{ Request::root() }}/guest/delivery/{{ $Delivery->uuid }}')">
                          <i class="fas fa-copy"></i> {{ __('general_content.copy_trans_key') }} 
                        </button>
                      </td>
                    </tr>
                    @endif
                    @if (!$allDelivered)
                    <tr>
                      <td> 
                      </td>
                      <td>
                        <a class="btn btn-primary btn-sm" href="{{ route('invoices.store.from.delivery', ['id' => $Delivery->id])}}" >{{ __('general_content.new_invoice_trans_key') }}</a>
                      </td>
                    </tr>
                    @endif
                </table>
              </div>
            </x-adminlte-card>
            
            @include('include.email-list', ['mailsList'=> $Delivery->emailLogs,])
          </div>
        </div>
      </div>      
      <div class="tab-pane " id="DeliveryLines">
        @php
          $dlLines = [];
          foreach ($Delivery->DeliveryLines as $dl) {
              $dlLines[] = [
                  'id'             => $dl->id,
                  'order_id'       => $dl->OrderLine->order['id'] ?? null,
                  'order_code'     => $dl->OrderLine->order['code'] ?? '',
                  'order_url'      => ($dl->OrderLine->order['id'] ?? null) ? route('orders.show', ['id' => $dl->OrderLine->order['id']]) : '#',
                  'code'           => $dl->OrderLine['code'] ?? '',
                  'label'          => $dl->OrderLine['label'] ?? '',
                  'qty'            => $dl->OrderLine['qty'] ?? '',
                  'unit'           => $dl->OrderLine->Unit['label'] ?? '',
                  'delivered_qty'  => $dl->qty,
                  'remaining_qty'  => $dl->OrderLine['delivered_remaining_qty'] ?? '',
                  'invoice_status' => $dl->invoice_status,
              ];
          }
          $dlNonConformities = [];
          foreach ($nonConformities as $nc) {
              $dlNonConformities[] = ['id' => $nc->id, 'code' => $nc->code];
          }
          $dlEndpoints = [
              'store'             => route('returns.json.store'),
              'markNotChargeable' => route('deliverys.line.not-chargeable', ['id' => $Delivery->id, 'lineId' => '__LINE__']),
          ];
          $dlTrans = [
              'order'          => __('general_content.order_trans_key'),
              'code'           => __('general_content.external_id_trans_key'),
              'description'    => __('general_content.description_trans_key'),
              'qty'            => __('general_content.qty_trans_key'),
              'unit'           => __('general_content.unit_trans_key'),
              'delivered_qty'  => __('general_content.delivered_qty_trans_key'),
              'remaining_qty'  => __('general_content.remaining_qty_trans_key'),
              'invoice_status' => __('general_content.invoice_status_trans_key'),
              'mark_not_chargeable'    => __('general_content.mark_not_chargeable_trans_key'),
              'confirm_not_chargeable' => __('general_content.confirm_not_chargeable_trans_key'),
              'return'         => __('returns.fields.add_return'),
              'add_return'     => __('returns.fields.add_return'),
              'delivery_line'  => __('returns.fields.delivery_line'),
              'non_conformity' => __('returns.fields.non_conformity'),
              'issue'          => __('returns.fields.issue'),
              'choose'         => __('returns.fields.choose'),
              'save'           => __('returns.fields.save'),
              'cancel'         => __('general_content.cancel_trans_key'),
              'no_data'        => __('general_content.no_data_trans_key'),
          ];
        @endphp
        <div
          id="delivery-lines-tab-app"
          data-lines='@json($dlLines, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
          data-delivery-id="{{ $Delivery->id }}"
          data-non-conformities='@json($dlNonConformities, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
          data-endpoints='@json($dlEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
          data-trans='@json($dlTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
        </div>
      </div>
      <div class="tab-pane" id="Photos">
        <div class="row">
          <div class="col-md-12">
            <x-adminlte-card title="{{ __('general_content.photos_trans_key') }}" theme="info" maximizable>
              <form action="{{ route('photo.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-file"></i></span>
                  </div>
                  <div class="custom-file">
                    <input type="hidden" name="deliverys_id" value="{{ $Delivery->id }}" >
                    <input type="file" name="file" accept="image/*" capture="camera" class="custom-file-input" id="chooseFile">
                    <label class="custom-file-label" for="chooseFile">{{ __('general_content.take_photo_trans_key') }}</label>
                  </div>
                  <div class="input-group-append">
                    <button type="submit" name="submit" class="btn btn-success">
                      {{ __('general_content.upload_trans_key') }} 
                    </button>
                  </div>
                </div>
              </form>
            </x-adminlte-card>
          </div>
        </div>
        <div class="row">
          @foreach($Delivery->photos as $photo)
              <div class="col-md-4 mb-4">
                  <div class="card">
                      <img src="{{ asset('photo/' . $photo->name) }}" class="card-img-top" alt="Photo" width="100">
                      <div class="card-body">
                          <p class="card-text">{{ $photo->original_file_name }}</p>
                      </div>
                  </div>
              </div>
          @endforeach
        </div>
      </div>
      <div class="tab-pane" id="Packaging">
        <div class="row">
          <div class="col-md-8">
            <x-adminlte-card title="{{ __('general_content.packaging_trans_key') }}" theme="primary" maximizable>
                <div class="table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.code_trans_key') }}</th>
                                <th>{{ __('general_content.type_trans_key') }}</th>
                                <th>{{ __('general_content.gross_weight_trans_key') }}</th>
                                <th>{{ __('general_content.weight_trans_key') }}</th>
                                <th>{{ __('general_content.x_size_trans_key') }}</th>
                                <th>{{ __('general_content.y_size_trans_key') }}</th>
                                <th>{{ __('general_content.z_size_trans_key') }}</th>
                                <th>{{ __('general_content.packing_date_trans_key') }}</th>
                                <th>{{ __('general_content.loaded_date_trans_key') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($Delivery->packaging as $Packaging)
                            <tr>
                                <td>{{ $Packaging->code }}</td>
                                <td>{{ $Packaging->type }}</td>
                                <td>{{ $Packaging->gross_weight }}</td>
                                <td>{{ $Packaging->net_weight }}</td>
                                <td>{{ $Packaging->length }}</td>
                                <td>{{ $Packaging->width }}</td>
                                <td>{{ $Packaging->height }}</td>
                                <td>{{ $Packaging->packing_date }}</td>
                                <td>{{ $Packaging->loaded_date }}</td>
                                <td class="py-0 align-middle">
                                    <!-- Button Modal -->
                                    <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Packaging{{ $Packaging->id }}">
                                        <i class="fa fa-lg fa-fw fa-edit"></i>
                                    </button>
                                    <!-- Modal {{ $Packaging->id }} -->
                                    <x-adminlte-modal id="Packaging{{ $Packaging->id }}" title="Update {{ $Packaging->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                        <form method="POST" action="{{ route('deliverys.packagings.update', ['id' => $Packaging->id]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">

                                              @csrf
                                              <div class="form-group">
                                                <label for="code">{{ __('general_content.code_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                      <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                                  </div>
                                                  <input type="text" class="form-control" name="code" id="code" value="{{ $Packaging->code }}" placeholder="{{ __('general_content.code_trans_key') }}">
                                                  <input type="hidden" name="id" id="id" value="{{ $Packaging->id }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="type">{{ __('general_content.type_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-box-open"></i></span>
                                                  </div>
                                                  <input type="text" class="form-control" name="type" id="type" value="{{ $Packaging->type }}" placeholder="{{ __('general_content.type_trans_key') }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="gross_weight">{{ __('general_content.gross_weight_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                                  </div>
                                                  <input type="number" class="form-control" name="gross_weight" id="gross_weight" step="0.01" value="{{ $Packaging->gross_weight }}"  placeholder="{{ __('general_content.gross_weight_trans_key') }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="net_weight">{{ __('general_content.weight_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-weight"></i></span>
                                                  </div>
                                                  <input type="number" class="form-control" name="net_weight" id="net_weight" step="0.01" value="{{ $Packaging->net_weight }}"  placeholder="{{ __('general_content.weight_trans_key') }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="length">{{ __('general_content.x_size_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-ruler-horizontal"></i></span>
                                                  </div>
                                                  <input type="number" class="form-control" name="length" id="length" step="0.01" value="{{ $Packaging->length }}"  placeholder="{{ __('general_content.x_size_trans_key') }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="width">{{ __('general_content.y_size_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                  </div>
                                                  <input type="number" class="form-control" name="width" id="width" step="0.01" value="{{ $Packaging->width }}"  placeholder="{{ __('general_content.y_size_trans_key') }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="height">{{ __('general_content.z_size_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                                  </div>
                                                  <input type="number" class="form-control" name="height" id="height" step="0.01" value="{{ $Packaging->height }}"  placeholder="{{ __('general_content.z_size_trans_key') }}">
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="comment">{{ __('general_content.comment_trans_key') }}</label>
                                                <textarea class="form-control" name="comment" id="comment" value="{{ $Packaging->comment }}"  placeholder="{{ __('general_content.comment_trans_key') }}"></textarea>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="packing_date">{{ __('general_content.packing_date_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                                  </div>
                                                  <input type="datetime-local" class="form-control" name="packing_date" id="packing_date" value="{{ $Packaging->packing_date }}" >
                                                </div>
                                              </div>
                                        
                                              <div class="form-group">
                                                <label for="loaded_date">{{ __('general_content.loaded_date_trans_key') }}</label>
                                                <div class="input-group">
                                                  <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                                  </div>
                                                  <input type="datetime-local" class="form-control" name="loaded_date" id="loaded_date" value="{{ $Packaging->loaded_date }}">
                                                </div>
                                              </div>

                                            </div>
                                            <div class="card-footer">
                                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                                            </div>
                                        </form>
                                    </x-adminlte-modal>
                                </td>
                            </tr>
                            @empty
                            <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}" />
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{{ __('general_content.code_trans_key') }}</th>
                                <th>{{ __('general_content.type_trans_key') }}</th>
                                <th>{{ __('general_content.gross_weight_trans_key') }}</th>
                                <th>{{ __('general_content.weight_trans_key') }}</th>
                                <th>{{ __('general_content.x_size_trans_key') }}</th>
                                <th>{{ __('general_content.x_size_trans_key') }}</th>
                                <th>{{ __('general_content.z_size_trans_key') }}</th>
                                <th>{{ __('general_content.packing_date_trans_key') }}</th>
                                <th>{{ __('general_content.loaded_date_trans_key') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-adminlte-card>
          </div>
          <div class="col-md-4">
            <form method="POST" action="{{ route('deliverys.packagings.store', ['id' => $Delivery->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.new_packaging_trans_key') }}" theme="secondary" maximizable>
                @csrf
                <div class="form-group">
                  <label for="code">{{ __('general_content.code_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                    </div>
                    <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.code_trans_key') }}">
                    <input type="hidden" name="deliverys_id" id="deliverys_id" value="{{ $Delivery->id }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="type">{{ __('general_content.type_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-box-open"></i></span>
                    </div>
                    <input type="text" class="form-control" name="type" id="type" placeholder="{{ __('general_content.type_trans_key') }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="gross_weight">{{ __('general_content.gross_weight_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                    </div>
                    <input type="number" class="form-control" name="gross_weight" id="gross_weight" step="0.01" placeholder="{{ __('general_content.gross_weight_trans_key') }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="net_weight">{{ __('general_content.weight_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-weight"></i></span>
                    </div>
                    <input type="number" class="form-control" name="net_weight" id="net_weight" step="0.01" placeholder="{{ __('general_content.weight_trans_key') }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="length">{{ __('general_content.x_size_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-ruler-horizontal"></i></span>
                    </div>
                    <input type="number" class="form-control" name="length" id="length" step="0.01" placeholder="{{ __('general_content.x_size_trans_key') }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="width">{{ __('general_content.y_size_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                    </div>
                    <input type="number" class="form-control" name="width" id="width" step="0.01" placeholder="{{ __('general_content.y_size_trans_key') }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="height">{{ __('general_content.z_size_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                    </div>
                    <input type="number" class="form-control" name="height" id="height" step="0.01" placeholder="{{ __('general_content.z_size_trans_key') }}">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="comment">{{ __('general_content.comment_trans_key') }}</label>
                  <textarea class="form-control" name="comment" id="comment" placeholder="{{ __('general_content.comment_trans_key') }}"></textarea>
                </div>
          
                <div class="form-group">
                  <label for="packing_date">{{ __('general_content.packing_date_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                    </div>
                    <input type="datetime-local" class="form-control" name="packing_date" id="packing_date">
                  </div>
                </div>
          
                <div class="form-group">
                  <label for="loaded_date">{{ __('general_content.loaded_date_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                    </div>
                    <input type="datetime-local" class="form-control" name="loaded_date" id="loaded_date">
                  </div>
                </div>
                
                <div class="card-footer">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
              </x-adminlte-card>
            </form>
          </div>
        </div>
      </div>
      {{-- ── Retours ────────────────────────────────────────────────── --}}
      <div class="tab-pane" id="Returns">
        <x-adminlte-card title="{{ __('general_content.returns_trans_key') }}" theme="warning" maximizable>
          <div class="table-responsive p-0">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>{{ __('returns.fields.code') }}</th>
                  <th>{{ __('returns.fields.label') }}</th>
                  <th>{{ __('returns.fields.status') }}</th>
                  <th>{{ __('returns.fields.non_conformity') }}</th>
                  <th>{{ __('general_content.date_trans_key') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($Delivery->returns as $ret)
                <tr>
                  <td><small>{{ $ret->code }}</small></td>
                  <td>{{ $ret->label }}</td>
                  <td>
                    @php
                      $badge = match($ret->statu) { 1 => 'badge-info', 2 => 'badge-primary', 3 => 'badge-warning', 4 => 'badge-success', default => 'badge-secondary' };
                    @endphp
                    <span class="badge {{ $badge }}">{{ $ret->status_label }}</span>
                  </td>
                  <td>
                    @if($ret->qualityNonConformity)
                      <span class="badge badge-light border">{{ $ret->qualityNonConformity->code }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td><small>{{ $ret->created_at?->format('d/m/Y') }}</small></td>
                  <td>
                    <a href="{{ route('returns.show', ['id' => $ret->id]) }}" class="btn btn-xs btn-outline-secondary">
                      <i class="fas fa-folder-open"></i>
                    </a>
                  </td>
                </tr>
                @empty
                <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                @endforelse
              </tbody>
            </table>
          </div>
        </x-adminlte-card>
      </div>

      {{-- ── Non-conformités ─────────────────────────────────────────── --}}
      <div class="tab-pane" id="NonConformities">
        <x-adminlte-card title="{{ __('general_content.non_conformities_trans_key') }}" theme="danger" maximizable>
          <div class="table-responsive p-0">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>{{ __('general_content.external_id_trans_key') }}</th>
                  <th>{{ __('general_content.label_trans_key') }}</th>
                  <th>{{ __('general_content.type_trans_key') }}</th>
                  <th>{{ __('general_content.qty_trans_key') }}</th>
                  <th>{{ __('general_content.status_trans_key') }}</th>
                  <th>{{ __('general_content.date_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($Delivery->QualityNonConformity as $nc)
                <tr>
                  <td><small>{{ $nc->code }}</small></td>
                  <td>{{ $nc->label }}</td>
                  <td><small>{{ $nc->type }}</small></td>
                  <td>{{ format_qty($nc->qty) }}</td>
                  <td>
                    @php
                      $ncBadge = match($nc->statu) { 1 => 'badge-danger', 3 => 'badge-success', default => 'badge-secondary' };
                      $ncLabel = match($nc->statu) { 1 => __('general_content.in_progress_trans_key'), 3 => __('general_content.finished_task_trans_key'), default => '-' };
                    @endphp
                    <span class="badge {{ $ncBadge }}">{{ $ncLabel }}</span>
                  </td>
                  <td><small>{{ $nc->GetPrettyCreatedAttribute() }}</small></td>
                </tr>
                @empty
                <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
                @endforelse
              </tbody>
            </table>
          </div>
        </x-adminlte-card>
      </div>

      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Delivery->id, 'type' => 'delivery'])
      </div>
      @endif
      <div class="tab-pane" id="Documents">
        @include('include.file-manager-mount', [
          'fileableType' => 'delivery',
          'fileableId'   => $Delivery->id,
        ])
      </div>
      <div class="tab-pane " id="Logs">
        @include('include.logs-viewer-mount', ['logsSubjectType' => 'App\Models\Workflow\Deliverys', 'logsSubjectId' => $Delivery->id])
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
  // Tab switching - vanilla JS fallback (Bootstrap 4/5 coexistence)
  document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.nav-pills a[data-toggle="tab"]').forEach(function (link) {
          link.addEventListener('click', function (e) {
              e.preventDefault();
              var targetId = this.getAttribute('href');
              var target = document.querySelector(targetId);
              if (!target) return;
              // Hide all panes
              target.parentElement.querySelectorAll(':scope > .tab-pane').forEach(function (p) {
                  p.classList.remove('active');
              });
              // Show target
              target.classList.add('active');
              // Update nav
              this.closest('.nav').querySelectorAll('.nav-link').forEach(function (l) {
                  l.classList.remove('active');
              });
              this.classList.add('active');
          });
      });
  });

  function copyToClipboard(text) {
      // Create a temporary textarea element
      var tempTextarea = document.createElement("textarea");
      tempTextarea.value = text;
      
      // Add it to the document body
      document.body.appendChild(tempTextarea);
      
      // Select the text in the textarea
      tempTextarea.select();
      tempTextarea.setSelectionRange(0, 99999); // For mobile devices
      
      // Copy the text inside the textarea to clipboard
      document.execCommand("copy");
      
      // Remove the temporary textarea
      document.body.removeChild(tempTextarea);
      
      // Optionally, you can show a message indicating that the text has been copied
      // alert("Lien copié dans le presse-papier !");
  }

  (function ($) {
      var acUrl = '{{ $companyAcUrl }}';

      $('#delivery_company_id').on('change', function () {
          var companyId = $(this).val();
          $.getJSON(acUrl, { company_id: companyId }, function (data) {
              var $addr = $('#delivery_address_id').empty();
              $.each(data.addresses, function (_, a) {
                  $addr.append($('<option>', { value: a.id, text: a.label }));
              });
              $addr.trigger('change');

              var $contact = $('#delivery_contact_id').empty();
              $.each(data.contacts, function (_, c) {
                  $contact.append($('<option>', { value: c.id, text: c.label }));
              });
              $contact.trigger('change');
          });
      });
  }(jQuery));
</script>
@stop