
<div>
    <div class="card">
        <div class="card-body">
            @if(session()->has('success'))
                <div class="alert alert-success" role="alert">
                    {{ session()->get('success') }}
                </div>
            @endif

            @if(session()->has('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session()->get('error') }}
                </div>
            @endif

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
                                @if($OrderStatu == 1 && 1 == $OrderLine->delivery_status)
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" wire:click="edit({{$OrderLine->id}})" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" wire:click="destroy({{$OrderLine->id}})" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
                                    </div>
                                @endif
                                <!-- Modal -->
                                <div class="modal fade" id="MainProcessModal{{$OrderLine->id}}" tabindex="-1" role="dialog" aria-labelledby="MainProcessModalTitle{{$OrderLine->id}}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                            <h5 class="modal-title" id="MainProcessModalTitle{{$OrderLine->id}}">Add line to BOM</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                            </div>
                                            <div class="modal-body">
                                                @include('include.Main-procces', ['route' => route('task.store', ['id' => $OrderLine->orders_id]),'id_page' => $OrderLine->orders_id, 'id_type' => 'order_lines_id', 'infoLine' => ['id_line' => $OrderLine->id, 'qty_line' => $OrderLine->qty], 'status_id'=>$status_id['id'] ,'TechnicalCut' => $OrderLine->TechnicalCut,'BOM' => $OrderLine->BOM])
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="Submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Modal -->
                                <div class="btn-group btn-group-sm">
                                    <a href="#" data-toggle="modal" data-target="#MainProcessModal{{$OrderLine->id}}"  class="btn btn-success"><i class="fa fa-lg fa-fw  fas fa-list"></i></a>
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
