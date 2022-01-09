
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
                    @include('livewire.order-line-update')
                @else
                    @include('livewire.order-line-create')
                @endif
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="card-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                    </div>
                    <input type="text" class="form-control" wire:model="search" placeholder="Search line">
                </div>
            </div>
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
                            <td>{{ $OrderLine->ORDRE }} - 
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="up({{ $OrderLine->id }})" class="btn btn-secondary"><i class="fa fa-lg fa-fw  fa-sort-amount-down"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="down({{ $OrderLine->id }})" class="btn btn-primary"><i class="fa fa-lg fa-fw  fa-sort-amount-up-alt"></i></a>
                                </div>
                            </td>
                            <td>{{ $OrderLine->CODE }}</td>
                            <td>@if($OrderLine->product_id ) {{ $OrderLine->Product['LABEL'] }}@endif</td>
                            <td>{{ $OrderLine->LABEL }}</td>
                            <td>
                                <a href="#" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Delivered qty : {{ $OrderLine->delivered_qty }} <br /> Invoiced qty : {{ $OrderLine->invoiced_qty }}">{{ $OrderLine->qty }}</a>
                            </td>
                            <td>{{ $OrderLine->Unit['LABEL'] }}</td>
                            <td>{{ $OrderLine->selling_price }} {{ $Factory->curency }}</td>
                            <td>{{ $OrderLine->discount }} %</td>
                            <td>{{ $OrderLine->VAT['RATE'] }} %</td>
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
                                                @include('include.Main-procces', ['route' => route('task.store', ['id' => $OrderLine->orders_id]),'id_page' => $OrderLine->orders_id, 'id_type' => 'order_lines_id', 'id_line' => $OrderLine->id, 'status_id'=>$status_id['id'] ,'task' => $OrderLine->Task])
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
                        <tr>
                            <td colspan="12">
                                <div class="flex justify-center items-center">
                                    <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No lines found ...</span>
                                </div>
                            </td>
                        </tr>
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
