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
      <li class="nav-item"><a class="nav-link" href="#Photos" data-toggle="tab">{{ __('general_content.photos_trans_key') }}</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }}</a></li>
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
                <div class="card card-body">
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Delivery->code }}
                    </div>
                    <div class="form-group col-md-4">
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
                    <div class="form-group col-md-4">
                      @include('include.form.form-input-label',['label' =>'Name of delivery', 'Value' =>  $Delivery->label])
                    </div>
                  </div>
                </div>
                <div class="card card-body">
                  <div class="row">
                    <x-FormTextareaComment  comment="{{ $Delivery->comment }}" />
                  </div>
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="secondary" maximizable>
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