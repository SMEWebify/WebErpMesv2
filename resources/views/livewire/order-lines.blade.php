
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
                            <th></th>
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
                            <td>{{ $OrderLine->selling_price }} {{ $Factory->curency }}</td>
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
                                        <x-adminlte-modal id="OrderLine{{ $OrderLine->id }}" title="Update detail information for {{ $OrderLine->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                            <form method="POST" action="{{ route('orders.update.detail.line', ['idOrder'=>  $OrderLine->orders_id, 'id' => $OrderLine->OrderLineDetails->id]) }}" enctype="multipart/form-data">
                                            @csrf
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
                                                <hr>
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
                                                    <hr>
                                                    <div class="row">
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->diameter }}" name="diameter" id="diameter"  placeholder="{{ __('general_content.diameter_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize"  placeholder="{{ __('general_content.diameter_oversize_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->material_loss_rate }}" name="material_loss_rate" id="material_loss_rate"  placeholder="{{ __('general_content.material_loss_rate_trans_key') }}" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="form-group col-md-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                                </div>
                                                                <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->finishing }}" name="finishing" id="finishing"  placeholder="{{ __('general_content.finishing_trans_key') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                        </div>
                                                        <div class="form-group col-md-4">
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <x-FormTextareaComment  label="Internal comment" name="internal_comment" comment="{{ $OrderLine->OrderLineDetails->internal_comment }}" />
                                                    </div>
                                                    <div class="row">
                                                        <x-FormTextareaComment  label="External comment" name="external_comment" comment="{{ $OrderLine->OrderLineDetails->external_comment }}" />
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                                </div>
                                            </form>
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
                                        </x-adminlte-modal>
                                    </div>
                                    <div class="input-group-prepend">
                                        @if( $OrderLine->product_id && $OrderLine->Product->drawing_file)
                                        <!-- Drawing link -->
                                        <a class="btn btn-info" href="{{ asset('drawing/'. $OrderLine->Product->drawing_file) }}" target="_blank"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                                        @endif
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            @if($OrderStatu == 1 && $OrderLine->delivery_status == 1 )
                                                <a href="#" class="dropdown-item " wire:click="duplicateLine({{$OrderLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> {{ __('general_content.copie_line_trans_key') }}</span></a>
                                                <a href="#" class="dropdown-item" wire:click="edit({{$OrderLine->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                                <a href="#" class="dropdown-item" wire:click="destroy({{$OrderLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> {{ __('general_content.delete_line_trans_key') }}</span></a>
                                                @if($OrderLine->product_id )
                                                <a href="#" class="dropdown-item" wire:click="breakDown({{$OrderLine->id}})"><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i>{{ __('general_content.break_down_task_trans_key') }}</span></a>
                                                @endif
                                                @else
                                                <p class="dropdown-item "><span class="text-info">Order curently {{ __('general_content.in_progress_trans_key') }}</span></p>
                                            @endif
                                            <a href="#" class="dropdown-item " wire:click="createNC({{$OrderLine->id}}, {{$OrderLine->order->companies_id}})" ><span class="text-warning"><i class="fa fa-light fa-fw  fa-exclamation"></i>{{ __('general_content.new_non_conformitie_trans_key') }}</span></a>
                                            
                                            @if($OrderLine->code && $OrderLine->label)
                                                <a href="#" class="dropdown-item" wire:click="CreatProduct({{$OrderLine->id}})" ><span class="text-success"><i class="fa fa-lg fa-fw fas fa-barcode"></i>{{ __('general_content.create_product_trans_key') }}</span></a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-warning" data-toggle="modal" data-target="#OrderLineTasks{{ $OrderLine->id }}">
                                            <i class="fa fa-lg fa-fw  fas fa-list"></i>
                                        </button>
                                        <!-- Modal {{ $OrderLine->id }} -->
                                        <x-adminlte-modal id="OrderLineTasks{{ $OrderLine->id }}" title="Task detail for {{ $OrderLine->label }}" theme="warning" icon="fa fa-pen" size='lg' disable-animations>
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
                                                                <td>{{ $Task->unit_cost }} {{ $Factory->curency }}</td>
                                                                <td>{{ $Task->Margin() }} %</td>
                                                                <td>{{ $Task->unit_price }} {{ $Factory->curency }}</td>
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
                                        </x-adminlte-modal>
                                        <a href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $OrderLine->getAllTaskCountAttribute() }}</span></a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ((($OrderLine->delivery_status != 3 && $OrderLine->order->type != 2) && ($OrderLine->delivery_status != 4 && $OrderLine->order->type != 2)))
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
                                    <a class="btn btn-primary btn-sm" wire:click="storeDelevery({{ $OrderId }})" href="#">
                                        <i class="fas fa-folder"></i>
                                        {{ __('general_content.new_delivery_note_trans_key') }}
                                    </a>
                                    
                                    or

                                    <a class="btn btn-primary btn-sm" wire:click="storeInvoice({{ $OrderId }})" href="#">
                                        <i class="fas fa-folder"></i>
                                        {{ __('general_content.new_invoice_trans_key') }}
                                    </a>
                                </div>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
