
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            @if($OrderStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="update">
                    <div class="row">
                        <div class="col-2">
                            <input type="hidden" wire:model="order_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeOrderLine">
                    <div class="row">
                        <div class="col-2">
                            <input type="hidden"  name="orders_id"  id="orders_id" value="1" wire:model="orders_id" >
                            @include('livewire.form.line-create')
                @endif
            @else
            <x-adminlte-alert theme="info" title="Info">
                The document status does not allow adding / modifying / deleting lines.
            </x-adminlte-alert>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sort</th>
                            <th>External ID</th>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Selling price</th>
                            <th>Discount</th>
                            <th>VAT type</th>
                            <th>Delivery date</th>
                            <th>Tasks status</th>
                            <th>Delivery status</th>
                            <th>Invoice status</th>
                            <th>Action</th>
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
                            <td>
                                @if($OrderLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $OrderLine->product_id])}}" />@endif
                            </td>
                            <td>{{ $OrderLine->label }}</td>
                            <td>
                                <a href="#" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Delivered qty : {{ $OrderLine->delivered_qty }} 
Invoiced qty : {{ $OrderLine->invoiced_qty }}">{{ $OrderLine->qty }}</a>
                            </td>
                            <td>{{ $OrderLine->Unit['label'] }}</td>
                            <td>{{ $OrderLine->selling_price }} {{ $Factory->curency }}</td>
                            <td>{{ $OrderLine->discount }} %</td>
                            <td>{{ $OrderLine->VAT['rate'] }} %</td>
                            <td>{{ $OrderLine->delivery_date }}</td>
                            <td>
                                @if(1 == $OrderLine->tasks_status )  <span class="badge badge-info">No task</span>@endif
                                @if(2 == $OrderLine->tasks_status )  <span class="badge badge-warning">Created</span>@endif
                                @if(3 == $OrderLine->tasks_status )  <span class="badge badge-success">In progress</span>@endif
                                @if(4 == $OrderLine->tasks_status )  <span class="badge badge-danger">Finished (all the tasks are finished)</span>@endif
                            </td>
                            <td>
                                @if(1 == $OrderLine->delivery_status )  <span class="badge badge-info">Not delivered</span>@endif
                                @if(2 == $OrderLine->delivery_status )  <span class="badge badge-warning">partly delivered</span>@endif
                                @if(3 == $OrderLine->delivery_status )  <span class="badge badge-success">delivered</span>@endif
                            </td>
                            <td>
                                @if(1 == $OrderLine->invoice_status )  <span class="badge badge-info">Not invoiced</span>@endif
                                @if(2 == $OrderLine->invoice_status )  <span class="badge badge-warning">Partly invoiced</span>@endif
                                @if(3 == $OrderLine->invoice_status )  <span class="badge badge-success">Invoiced</span>@endif
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
                                                    <div class="col-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" value="{{ $OrderLine->OrderLineDetails->material }}" name="material" id="material" placeholder="Material">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->thickness }}" name="thickness" id="thickness" placeholder="Thickness" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->weight }}" name="weight" id="weight" placeholder="Weight" step=".001">
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label for="x_size">X</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $OrderLine->OrderLineDetails->x_size }}" name="x_size" id="x_size" placeholder="X size" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <label for="y_size">Y</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $OrderLine->OrderLineDetails->y_size }}"  name="y_size" id="y_size" placeholder="Y size" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <label for="z_size">Z</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $OrderLine->OrderLineDetails->z_size }}" name="z_size" id="z_size" placeholder="Z size" step=".001">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control"  value="{{ $OrderLine->OrderLineDetails->x_oversize }}" name="x_oversize" id="x_oversize" placeholder="X oversize" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->y_oversize }}" name="y_oversize" id="y_oversize" placeholder="Y oversize" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->z_oversize }}" name="z_oversize" id="z_oversize" placeholder="Z oversize" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->diameter }}" name="diameter" id="diameter" placeholder="Diameter" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize" placeholder="Diameter_oversize" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $OrderLine->OrderLineDetails->material_loss_rate }}" name="material_loss_rate" id="material_loss_rate" placeholder="Material loss rate" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="success" icon="fas fa-lg fa-save"/>
                                                </div>
                                            </form>
                                            <div class="card-body">
                                                <form action="{{ route('orders.update.detail.picture', ['idOrder'=>  $OrderLine->orders_id, 'id' => $OrderLine->OrderLineDetails->id]) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <label for="picture">Picture file</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-image"></i></span>
                                                        </div>
                                                        <div class="custom-file">
                                                            <input type="hidden" name="id" value="{{ $OrderLine->id }}">
                                                            <input type="file" class="custom-file-input" name="picture" id="picture">
                                                            <label class="custom-file-label" for="picture">Choose file</label>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <button type="submit" class="btn btn-success">Upload</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-adminlte-modal>
                                    </div>
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            @if($OrderStatu == 1 && $OrderLine->delivery_status == 1 )
                                            <a href="#" class="dropdown-item " wire:click="duplicateLine({{$OrderLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> Copy line</span></a>
                                            <a href="#" class="dropdown-item" wire:click="edit({{$OrderLine->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> Edit line</span></a>
                                            <a href="#" class="dropdown-item" wire:click="destroy({{$OrderLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> Delete line</span></a>
                                            @if($OrderLine->product_id )
                                            <a href="#" class="dropdown-item" wire:click="breakDown({{$OrderLine->id}})"><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i>Break down the article task</span></a>
                                            @endif
                                            @else
                                            <p class="dropdown-item "><span class="text-info">Order curently in progress</span></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> Tasks {{  $OrderLine->getTaskCountAttribute() }}</span></a></button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="12" text="No line found ..."  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Sort</th>
                            <th>External ID</th>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Selling price</th>
                            <th>Discount</th>
                            <th>VAT type</th>
                            <th>Delivery date</th>
                            <th>Tasks status</th>
                            <th>Delivery status</th>
                            <th>Invoice status</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
