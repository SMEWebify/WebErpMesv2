
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            @if($OrderStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="update">
                            <input type="hidden" wire:model.live="order_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeOrderLine">
                            <input type="hidden"  name="orders_id"  id="orders_id" value="1" wire:model.live="orders_id" >
                            @include('livewire.form.line-create')
                @endif
                @include('livewire.form.customer-price-grid')
            @else
            <x-adminlte-alert theme="info" title="Info">
                {{ __('general_content.info_statu_trans_key') }}
            </x-adminlte-alert>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    @include('include.search-card')
                </div>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{ __('general_content.tasks_status_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_status_trans_key') }}</th>
                            <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                            <th>
                                @if($OrderStatu != 6 && $OrderType != 2)
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            class="custom-control-input"
                                            id="select-all-order-lines"
                                            type="checkbox"
                                            wire:click="toggleSelectAllLines"
                                            @checked($selectAllLines)
                                        >
                                        <label class="custom-control-label" for="select-all-order-lines">
                                            {{ $selectAllLines ? __('general_content.deselect_all_lines_trans_key') : __('general_content.select_all_lines_trans_key') }}
                                        </label>
                                    </div>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($OrderLineslist as $OrderLine)
                        <tr>
                            <td> 
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="up({{ $OrderLine->id }})" class="btn btn-secondary"><i class="fas fa-sort-up"></i></a>
                                    <span class="btn btn-info">{{ $OrderLine->ordre }}</span>
                                    <a href="#" wire:click="down({{ $OrderLine->id }})" class="btn btn-primary"><i class="fas fa-sort-down"></i></a>
                                </div>
                            </td>
                            <td>{{ $OrderLine->code }}</td>
                            <td   @if($OrderLine->product_id )  class="bg-{{ $OrderLine->Product->getColorStockStatu() }} color-palette" @endif>
                                @if($OrderLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $OrderLine->product_id])}}" />@endif
                            </td>
                            <td>{{ $OrderLine->label }}</td>
                            <td>{{ $OrderLine->qty }}</td>
                            <td>{{ $OrderLine->Unit['label'] }}</td>
                            <td @if($OrderLine->use_calculated_price) class="bg-warning color-palette" @endif>
                                {{ $OrderLine->formatted_selling_price }}
                            </td>
                            <td>{{ $OrderLine->discount }} %</td>
                            <td>{{ $OrderLine->VAT['rate'] }} %</td>
                            <td><a href="#" class="btn btn-primary btn-sm" data-toggle="tooltip" title="{{ __('general_content.internal_delay_trans_key') }} : {{ $OrderLine->internal_delay }}">{{ $OrderLine->delivery_date }}</a>
                                
                                </td>
                            <td>
                                @if(1 == $OrderLine->tasks_status )  <span class="badge badge-info">{{ __('general_content.no_task_trans_key') }}</span>@endif
                                @if(2 == $OrderLine->tasks_status )  
                                    <span class="badge badge-warning">{{ __('general_content.created_trans_key') }}</span> 
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressTaskAttribute() }}" with-label animated/>
                                @endif
                                @if(3 == $OrderLine->tasks_status )  
                                    <span class="badge badge-success">{{ __('general_content.in_progress_trans_key') }}</span>
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressTaskAttribute() }}" with-label animated/>
                                @endif
                                @if(4 == $OrderLine->tasks_status )  
                                    <span class="badge badge-danger">{{ __('general_content.finished_task_trans_key') }}</span>
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressTaskAttribute() }}" with-label animated/>
                                @endif
                            </td>
                            <td>
                                @if($OrderLine->order->type == 2)
                                    @if(1 == $OrderLine->delivery_status )  <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>@endif
                                    @if(2 == $OrderLine->delivery_status )  <span class="badge badge-warning">{{ __('general_content.partly_stored_trans_key') }}</span>@endif
                                    @if(3 == $OrderLine->delivery_status )  <span class="badge badge-success">{{ __('general_content.stock_trans_key') }}</span>@endif
                                @else
                                    @if(1 == $OrderLine->delivery_status )  <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>@endif
                                    @if(2 == $OrderLine->delivery_status )  
                                    <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $OrderLine->id }}"><span class="badge badge-warning">{{ __('general_content.partly_delivered_trans_key') }} ({{ $OrderLine->delivered_qty }} )</span></a>
                                    @endif
                                    @if(3 == $OrderLine->delivery_status )  
                                    <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $OrderLine->id }}"><span class="badge badge-success">{{ __('general_content.delivered_trans_key') }} ({{ $OrderLine->delivered_qty }} )</span></a>
                                    @endif
                                    @if(4 == $OrderLine->delivery_status )  <span class="badge badge-primary" >{{ __('general_content.delivered_without_dn_trans_key') }} ({{ $OrderLine->delivered_qty }} )</span>@endif
                                
                                    {{-- Modal for delivery detail --}}
                                    <x-adminlte-modal id="modalDeliveryFor{{ $OrderLine->id }}" title="{{__('general_content.deliverys_notes_list_trans_key') }}" theme="info"
                                        icon="fas fa-bolt" size='lg' disable-animations>
                                        <ul>
                                            @foreach($OrderLine->DeliveryLines as $deliveryLine)
                                                <li>
                                                    {{ __('general_content.delivery_notes_trans_key') }}: {{ $deliveryLine->delivery->code }} <br>
                                                    {{ __('general_content.qty_trans_key') }} : {{ $deliveryLine->qty }} <br>
                                                    {{__('general_content.created_at_trans_key') }} : {{ $deliveryLine->GetPrettyCreatedAttribute() }} <br>
                                                    <x-ButtonTextView route="{{ route('deliverys.show', ['id' => $deliveryLine->deliverys_id])}}" />
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-adminlte-modal>
                                
                                @endif
                                @if(1 != $OrderLine->delivery_status )
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressDeleveryAttribute() }}" with-label animated/>
                                @endif
                            </td>
                            <td>
                                @if($OrderLine->order->type == 2)
                                    -
                                @else
                                    @if(1 == $OrderLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.not_invoiced_trans_key') }}</span>@endif
                                    @if(2 == $OrderLine->invoice_status )
                                    <a href="#" data-toggle="modal" data-target="#modalInvoiceFor{{ $OrderLine->id }}"><span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }} ({{ $OrderLine->invoiced_qty }} )</span></a>
                                    @endif
                                    @if(3 == $OrderLine->invoice_status )
                                    <a href="#" data-toggle="modal" data-target="#modalInvoiceFor{{ $OrderLine->id }}"><span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }} ({{ $OrderLine->invoiced_qty }} )</span></a>
                                    @endif

                                    {{-- Modal for delivery detail --}}
                                    <x-adminlte-modal id="modalInvoiceFor{{ $OrderLine->id }}" title="{{__('general_content.invoices_list_trans_key') }}" theme="info"
                                        icon="fas fa-bolt" size='lg' disable-animations>
                                        <ul>
                                            @foreach($OrderLine->InvoiceLines as $InvoiceLine)
                                                <li>
                                                    {{ __('general_content.invoices_trans_key') }} : {{ $InvoiceLine->invoice->code }} <br>
                                                    {{ __('general_content.qty_trans_key') }} : {{ $InvoiceLine->qty }} <br>
                                                    {{__('general_content.created_at_trans_key') }} : {{ $InvoiceLine->GetPrettyCreatedAttribute() }} <br>
                                                    <x-ButtonTextView route="{{ route('invoices.show', ['id' => $InvoiceLine->invoices_id])}}" />
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-adminlte-modal>
                                
                                    @if(1 != $OrderLine->invoice_status )
                                        <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressInvoiceAttribute() }}" with-label animated/>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="input-group mb-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#OrderLine{{ $OrderLine->id }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <!-- Modal {{ $OrderLine->id }} -->
                                        <x-adminlte-modal wire:ignore.self id="OrderLine{{ $OrderLine->id }}" title="Update detail information for {{ $OrderLine->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                            <form method="POST" action="{{ route('orders.update.detail.line', ['idOrder'=>  $OrderLine->orders_id, 'id' => $OrderLine->OrderLineDetails->id]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">
                                                <div class="accordion" id="orderLineDetailAccordion{{ $OrderLine->id }}">
                                                    <div class="card card-outline card-success mb-2">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineMainFeatures{{ $OrderLine->id }}" aria-expanded="true" aria-controls="orderLineMainFeatures{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-stream text-success mr-2"></i> {{ __('Caractéristiques principales') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineMainFeatures{{ $OrderLine->id }}" class="collapse show" aria-labelledby="orderLineMainFeatures{{ $OrderLine->id }}" data-parent="#orderLineDetailAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="form-group col-md-4">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                                            </div>
                                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->material }}" name="material" id="material"  placeholder="{{ __('general_content.material_trans_key') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->thickness }}" name="thickness" id="thickness"  placeholder="{{ __('general_content.thickness_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->weight }}" name="weight" id="weight"  placeholder="{{ __('general_content.weight_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="card card-outline card-primary mb-2">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineDimensions{{ $OrderLine->id }}" aria-expanded="false" aria-controls="orderLineDimensions{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-ruler-combined text-primary mr-2"></i> {{ __('Dimensions (X, Y, Z)') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineDimensions{{ $OrderLine->id }}" class="collapse" aria-labelledby="orderLineDimensions{{ $OrderLine->id }}" data-parent="#orderLineDetailAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="form-group col-md-4">
                                                                        <label for="x_size">X</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{  $OrderLine->OrderLineDetails->x_size }}" name="x_size" id="x_size"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label for="y_size">Y</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{  $OrderLine->OrderLineDetails->y_size }}"  name="y_size" id="y_size"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-4">
                                                                        <label for="z_size">Z</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{  $OrderLine->OrderLineDetails->z_size }}" name="z_size" id="z_size"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                        <div class="form-group col-md-4">
                                                                            <div class="input-group">
                                                                                <div class="input-group-prepend">
                                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                                </div>
                                                                                <input type="number" class="form-control"  value="{{ $OrderLine->OrderLineDetails->x_oversize }}" name="x_oversize" id="x_oversize"  placeholder="{{ __('general_content.x_oversize_trans_key') }}" step=".001">
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group col-md-4">
                                                                            <div class="input-group">
                                                                                <div class="input-group-prepend">
                                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                                </div>
                                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->y_oversize }}" name="y_oversize" id="y_oversize"  placeholder="{{ __('general_content.y_oversize_trans_key') }}" step=".001">
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group col-md-4">
                                                                            <div class="input-group">
                                                                                <div class="input-group-prepend">
                                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                                </div>
                                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->z_oversize }}" name="z_oversize" id="z_oversize"  placeholder="{{ __('general_content.z_oversize_trans_key') }}" step=".001">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="card card-outline card-warning mb-2">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineCircularSpecs{{ $OrderLine->id }}" aria-expanded="false" aria-controls="orderLineCircularSpecs{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-circle-notch text-warning mr-2"></i> {{ __('Spécifications circulaires') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineCircularSpecs{{ $OrderLine->id }}" class="collapse" aria-labelledby="orderLineCircularSpecs{{ $OrderLine->id }}" data-parent="#orderLineDetailAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="form-group col-md-3">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->diameter }}" name="diameter" id="diameter"  placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-3">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize"  placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-3">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->bend_count }}" name="bend_count" id="bend_count"  placeholder="{{ __('general_content.bend_count_trans_key') }}" step="1" min="0">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-3">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                                            </div>
                                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->material_loss_rate }}" name="material_loss_rate" id="material_loss_rate"  placeholder="{{ __('general_content.material_loss_rate_trans_key') }}" step=".001">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="card card-outline card-success mb-2">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineFiles{{ $OrderLine->id }}" aria-expanded="false" aria-controls="orderLineFiles{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-paperclip text-success mr-2"></i> {{ __('Fichiers') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineFiles{{ $OrderLine->id }}" class="collapse" aria-labelledby="orderLineFiles{{ $OrderLine->id }}" data-parent="#orderLineDetailAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="form-group col-md-6">
                                                                        <label for="cad_file">{{ __('CAD file') }}</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-drafting-compass"></i></span>
                                                                            </div>
                                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->cad_file }}" name="cad_file" id="cad_file" placeholder="{{ __('CAD file name') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-6">
                                                                        <label for="cam_file">{{ __('CAM file') }}</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-cogs"></i></span>
                                                                            </div>
                                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->cam_file }}" name="cam_file" id="cam_file" placeholder="{{ __('CAM file name') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="form-group col-md-6">
                                                                        <label for="cad_file_path">{{ __('CAD file path') }}</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                                                                            </div>
                                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->cad_file_path }}" name="cad_file_path" id="cad_file_path" placeholder="{{ __('CAD file path') }}">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-6">
                                                                        <label for="cam_file_path">{{ __('CAM file path') }}</label>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fas fa-folder-open"></i></span>
                                                                            </div>
                                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->cam_file_path }}" name="cam_file_path" id="cam_file_path" placeholder="{{ __('CAM file path') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @php $orderDetailId = $OrderLine->OrderLineDetails->id; @endphp
                                                    <div class="card card-outline card-danger mb-2">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineCustomReq{{ $OrderLine->id }}" aria-expanded="false" aria-controls="orderLineCustomReq{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-tasks text-danger mr-2"></i> {{ __('Exigences personnalisées') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineCustomReq{{ $OrderLine->id }}" class="collapse" aria-labelledby="orderLineCustomReq{{ $OrderLine->id }}" data-parent="#orderLineDetailAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <label class="text-info">{{ __('Custom requirements') }}</label>
                                                                    </div>
                                                                    @forelse($customRequirements[$orderDetailId] ?? [] as $index => $requirement)
                                                                        <div class="form-row align-items-end w-100" wire:key="order-custom-{{ $orderDetailId }}-{{ $index }}">
                                                                            <div class="form-group col-md-5">
                                                                                <label for="order_custom_requirement_label_{{ $orderDetailId }}_{{ $index }}">{{ __('Label') }}</label>
                                                                                <input type="text" class="form-control" id="order_custom_requirement_label_{{ $orderDetailId }}_{{ $index }}" name="custom_requirements[{{ $index }}][label]" wire:model="customRequirements.{{ $orderDetailId }}.{{ $index }}.label" placeholder="{{ __('Label') }}">
                                                                            </div>
                                                                            <div class="form-group col-md-5">
                                                                                <label for="order_custom_requirement_value_{{ $orderDetailId }}_{{ $index }}">{{ __('Value') }}</label>
                                                                                <input type="text" class="form-control" id="order_custom_requirement_value_{{ $orderDetailId }}_{{ $index }}" name="custom_requirements[{{ $index }}][value]" wire:model="customRequirements.{{ $orderDetailId }}.{{ $index }}.value" placeholder="{{ __('Value') }}">
                                                                            </div>
                                                                            <div class="form-group col-md-2">
                                                                                <button type="button" class="btn btn-outline-danger mt-4" wire:click="removeCustomRequirement({{ $orderDetailId }}, {{ $index }})"><i class="fas fa-trash"></i></button>
                                                                            </div>
                                                                        </div>
                                                                    @empty
                                                                        <div class="col-12">
                                                                            <p class="text-muted">{{ __('No custom requirement added yet.') }}</p>
                                                                        </div>
                                                                    @endforelse
                                                                    <div class="col-12 mb-3">
                                                                        <button type="button" class="btn btn-outline-primary" wire:click="addCustomRequirement({{ $orderDetailId }})"><i class="fas fa-plus"></i> {{ __('Add requirement') }}</button>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="form-group col-md-6">
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                                            </div>
                                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->finishing }}" name="finishing" id="finishing"  placeholder="{{ __('general_content.finishing_trans_key') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="card card-outline card-secondary mb-0">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineComments{{ $OrderLine->id }}" aria-expanded="false" aria-controls="orderLineComments{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-comments text-secondary mr-2"></i> {{ __('Commentaires') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineComments{{ $OrderLine->id }}" class="collapse" aria-labelledby="orderLineComments{{ $OrderLine->id }}" data-parent="#orderLineDetailAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <x-FormTextareaComment  label="Internal comment" name="internal_comment" comment="{{ $OrderLine->OrderLineDetails->internal_comment }}" />
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <x-FormTextareaComment  label="External comment" name="external_comment" comment="{{ $OrderLine->OrderLineDetails->external_comment }}" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                                </div>
                                            </form>
                                            <div class="card-body">
                                                <div class="accordion" id="orderLineAttachmentAccordion{{ $OrderLine->id }}">
                                                    <div class="card card-outline card-success mb-0">
                                                        <div class="card-header">
                                                            <button class="btn btn-link text-left w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#orderLineAttachments{{ $OrderLine->id }}" aria-expanded="true" aria-controls="orderLineAttachments{{ $OrderLine->id }}">
                                                                <span class="d-flex align-items-center">
                                                                    <i class="fas fa-paperclip text-success mr-2"></i> {{ __('Fichiers attachés') }}
                                                                </span>
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="orderLineAttachments{{ $OrderLine->id }}" class="collapse show" aria-labelledby="orderLineAttachments{{ $OrderLine->id }}" data-parent="#orderLineAttachmentAccordion{{ $OrderLine->id }}">
                                                            <div class="card-body">
                                                                <form action="{{ route('orders.update.detail.picture', ['idOrder'=>  $OrderLine->orders_id, 'id' => $OrderLine->OrderLineDetails->id]) }}" method="POST" enctype="multipart/form-data">
                                                                    @csrf
                                                                    <label for="picture">{{ __('general_content.picture_file_trans_key') }}</label>(peg,png,jpg,gif,svg | max: 10 240 Ko)
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text"><i class="far fa-image"></i></span>
                                                                        </div>
                                                                        <div class="custom-file">
                                                                            <input type="hidden" name="id" value="{{ $OrderLine->id }}">
                                                                            <input type="file" class="custom-file-input" name="picture" id="picture">
                                                                            <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}</label>
                                                                        </div>
                                                                        <div class="input-group-append">
                                                                            <button type="submit" class="btn btn-success">{{ __('general_content.upload_trans_key') }}</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-adminlte-modal>
                                    </div>
                                    <div class="input-group-prepend">
                                        @if( $OrderLine->product_id && $OrderLine->Product->drawing_file)
                                        <!-- Drawing link -->
                                        <x-button-text-view :bankFile="$OrderLine->Product->drawing_file" />
                                        @endif
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            @if($OrderStatu == 1 && $OrderLine->delivery_status == 1 )
                                                <a href="#" class="dropdown-item " wire:click="duplicateLine({{$OrderLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> {{ __('general_content.copie_line_trans_key') }}</span></a>
                                                <a href="#" class="dropdown-item" wire:click="edit({{$OrderLine->id}})"><span class="text-warning"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                                <a href="#" class="dropdown-item" wire:click="destroy({{$OrderLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.delete_line_trans_key') }}</span></a>
                                                @if($OrderLine->product_id )
                                                    <a href="#" class="dropdown-item" wire:click="breakDown({{$OrderLine->id}})"><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i>{{ __('general_content.break_down_task_trans_key') }}</span></a>
                                                @endif
                                            @else
                                                <p class="dropdown-item "><span class="text-info">Order curently {{ __('general_content.in_progress_trans_key') }}</span></p>
                                            @endif
                                            <a href="#" class="dropdown-item " wire:click="createNC({{$OrderLine->id}}, {{$OrderLine->order->companies_id}})" ><span class="text-warning"><i class="fa fa-light fa-fw  fa-exclamation"></i>{{ __('general_content.new_non_conformitie_trans_key') }}</span></a>
                                            
                                            @if($OrderLine->code && $OrderLine->label)
                                                <a href="#" class="dropdown-item" wire:click="createProduct({{$OrderLine->id}})" ><span class="text-success"><i class="fa fa-lg fa-fw fas fa-barcode"></i>{{ __('general_content.create_product_trans_key') }}</span></a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-warning" data-toggle="modal" data-target="#OrderLineTasks{{ $OrderLine->id }}">
                                            <i class="fa fa-lg fa-fw  fas fa-list"></i>
                                        </button>
                                        <!-- Modal {{ $OrderLine->id }} -->
                                        <x-adminlte-modal id="OrderLineTasks{{ $OrderLine->id }}" title="Task detail for {{ $OrderLine->label }}" theme="warning" icon="fa fa-pen" size='xl' disable-animations>
                                            <div class="card-body">
                                                <div class="row">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('general_content.order_trans_key') }}</th>
                                                                <th>{{ __('general_content.label_trans_key') }}</th>
                                                                <th>{{ __('general_content.service_trans_key') }}</th>
                                                                <th>{{ __('general_content.total_time_trans_key') }}</th>
                                                                <th>{{ __('general_content.qty_trans_key') }}</th>
                                                                <th>{{ __('general_content.cost_trans_key') }}</th>
                                                                <th>{{ __('general_content.margin_trans_key') }}</th>
                                                                <th>{{ __('general_content.price_trans_key') }}</th>
                                                                <th>{{ __('general_content.trs_trans_key') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse ( $OrderLine->Task as $Task)
                                                            <tr>
                                                                <td>{{ $Task->ordre }}</td>
                                                                <td>{{ $Task->label }}</td>
                                                                <td @if($Task->methods_services_id ) style="background-color: {{ $Task->service['color'] }};" @endif >
                                                                    @if($Task->methods_services_id )
                                                                        @if( $Task->service['picture'])
                                                                            <p data-toggle="tooltip" data-html="true" title="<img alt='Service' class='profile-user-img img-fluid img-circle' src='{{ asset('/images/methods/'. $Task->service['picture']) }}'>">
                                                                                <span>{{ $Task->service['label'] }}</span>
                                                                            </p>
                                                                        @else
                                                                            {{ $Task->service['label'] }}
                                                                        @endif
                                                                    @endif
                                                                </td>
                                                                <td>{{ $Task->TotalTime() }} h</td>
                                                                <td>{{ $Task->qty }}</td>
                                                                <td>{{ $Task->formatted_unit_price }}</td>
                                                                <td>{{ $Task->Margin() }} %</td>
                                                                <td>{{ $Task->formatted_unit_price }}</td>
                                                                <td>{{ $Task->getTRSAttribute() }} %</td>
                                                            </tr>
                                                            @empty
                                                            <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}"  />
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <a class="btn btn-info btn-sm" href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}">
                                                    <i class="fas fa-folder"></i>
                                                    {{ __('general_content.view_trans_key') }}
                                                </a>
                                            </div>
                                            @if($OrderStatu == 1)
                                            <div class="card-footer">
                                                <div class="btn-group" role="group">
                                                    @if(!$OrderLine->use_calculated_price)
                                                    <!-- Button for use calculated price -->
                                                    <button type="button" class="btn btn-success"
                                                            wire:click="enableCalculatedPrice({{ $OrderLine->id }})">
                                                            {{ __('general_content.active_calculated_price_trans_key') }}
                                                    </button>
                                                    @else
                                                    <!-- Button for disable calculated price -->
                                                    <button type="button" class="btn btn-warning"
                                                            wire:click="disableCalculatedPrice({{ $OrderLine->id }})">
                                                            {{ __('general_content.deactivate_calculated_price_trans_key') }}
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
                                        </x-adminlte-modal>
                                        <a href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $OrderLine->getAllTaskCountAttribute() }}</span></a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($OrderStatu != 6 && (($OrderLine->delivery_status != 3 && $OrderLine->order->type != 2) && ($OrderLine->delivery_status != 4 && $OrderLine->order->type != 2)))
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $OrderLine->id }}" wire:model.live="data.{{ $OrderLine->id }}.order_line_id" id="data.{{ $OrderLine->id }}.order_line_id"  type="checkbox">
                                    <label for="data.{{ $OrderLine->id }}.order_line_id" class="custom-control-label">+</label>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="14" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{ __('general_content.tasks_status_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_status_trans_key') }}</th>
                            <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="11"></th>
                            <th colspan="4">
                                <div>
                                    <label for="RemoveFromStock">{{ __('general_content.remove_component_lines_stock_trans_key') }}</label>
                                    <input type="checkbox" id="RemoveFromStock" wire:model.live="RemoveFromStock" >
                                </div>
                                <div>
                                    <label for="CreateSerialNumber">{{ __('general_content.create_serial_number_trans_key') }}</label>
                                    <input type="checkbox" id="CreateSerialNumber" wire:model.live="CreateSerialNumber" >
                                </div>
                                <div>
                                    @if($OrderStatu != 6)
                                        <a class="btn btn-primary btn-sm" wire:click="storeDelevery({{ $OrderId }})" href="#">
                                            <i class="fas fa-folder"></i>
                                            {{ __('general_content.new_delivery_note_trans_key') }}
                                        </a>
                                        
                                        or

                                        <a class="btn btn-primary btn-sm" wire:click="storeInvoice({{ $OrderId }})" href="#">
                                            <i class="fas fa-folder"></i>
                                            {{ __('general_content.new_invoice_trans_key') }}
                                        </a>
                                    @else
                                        <span class="badge badge-danger">{{ __('general_content.canceled_trans_key') }}</span>
                                        <small class="text-muted ms-2">{{ __('general_content.order_canceled_no_document_trans_key') }}</small>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
