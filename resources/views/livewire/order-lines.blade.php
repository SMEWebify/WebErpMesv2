
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
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
            <div class="table-responsive">
                <table class="table">
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
                            <td>{{ $OrderLine->ordre }} - 
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="up({{ $OrderLine->id }})" class="btn btn-secondary"><i class="fa fa-lg fa-fw  fa-sort-amount-down"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="down({{ $OrderLine->id }})" class="btn btn-primary"><i class="fa fa-lg fa-fw  fa-sort-amount-up-alt"></i></a>
                                </div>
                            </td>
                            <td>{{ $OrderLine->code }}</td>
                            <td>@if($OrderLine->product_id ) {{ $OrderLine->Product['label'] }}@endif</td>
                            <td>{{ $OrderLine->label }}</td>
                            <td>
                                <a href="#" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Delivered qty : {{ $OrderLine->delivered_qty }} <br /> Invoiced qty : {{ $OrderLine->invoiced_qty }}">{{ $OrderLine->qty }}</a>
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
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            @if($OrderStatu == 1 && 1 == $OrderLine->delivery_status)
                                            <a href="#" class="dropdown-item " wire:click="duplicateLine({{$OrderLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> Copy line</span></a>
                                            <a href="#" class="dropdown-item" wire:click="edit({{$OrderLine->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> Edit line</span></a>
                                            <a href="#" class="dropdown-item" wire:click="destroy({{$OrderLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> Delete line</span></a>
                                            @else
                                            <p class="dropdown-item "><span class="text-info">Order curently in progress</span></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> Tasks ({{  $OrderLine->getTaskCountAttribute() }})</span></a></button>
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
