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
      <li class="nav-item"><a class="nav-link" href="#DeliveryLines" data-toggle="tab">{{ __('general_content.delivery_lines_trans_key') }} ({{ count($Delivery->DeliveryLines) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Photos" data-toggle="tab">{{ __('general_content.photos_trans_key') }} ({{ count($Delivery->photos) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Packaging" data-toggle="tab">{{ __('general_content.packaging_trans_key') }} ({{ count($Delivery->packaging) }})</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Delivery">
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
                    <x-adminlte-select name="statu" label="{{ __('general_content.status_trans_key') }}" label-class="text-success" igroup-size="sm">
                      <x-slot name="prependSlot">
                          <div class="input-group-text bg-gradient-success">
                              <i class="fas fa-exclamation"></i>
                          </div>
                      </x-slot>
                      <option value="1" @if(1 == $Delivery->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                      <option value="2" @if(2 == $Delivery->statu ) Selected @endif >{{ __('general_content.send_trans_key') }}</option>
                    </x-adminlte-select>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.name_of_deliverys_notes_trans_key'), 'Value' =>  $Delivery->label])
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
                      <option value="NULL">{{ __('general_content.select_purchase_order_trans_key') }}</option>
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
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
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
                @if($Delivery->purchases_id)
                <div class="card-body">
                  {{ __('general_content.name_purchase_trans_key') }} : 
                  <a class="btn btn-primary btn-sm" href="{{ route('purchases.show', ['id' => $Delivery->purchases_id])}}">
                    <i class="fas fa-folder"></i>
                    {{ $Delivery->purchase->code }}
                  </a>
                </div>
                <div class="card-body">
                  {{ __('general_content.suppliers_trans_key') }} : <x-CompanieButton id="{{ $Delivery->purchase->companies_id }}" label="{{ $Delivery->purchase->companie['label'] }}"  />
                </div>
                @endif
              </div>
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                    <tr>
                        <td style="width:50%"> 
                          {{ __('general_content.delivery_notes_trans_key') }}
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.delivery', ['Document' => $Delivery->id])}}" />
                        </td>
                    </tr> 
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
            
            @include('include.file-store', ['inputName' => "deliverys_id",'inputValue' => $Delivery->id,'filesList' => $Delivery->files,])
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
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Delivery->id, 'type' => 'delivery'])
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
  <script>
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
</script>
@stop