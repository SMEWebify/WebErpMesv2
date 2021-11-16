
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
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Sort</th>
                            <th>Description</th>
                            <th>Product</th>
                            <th>Service</th>
                            <th>Qty</th>
                            <th>Qty init</th>
                            <th>Qty aviable</th>
                            <th>Unit</th>
                            <th>Unit cost</th>
                            <th>Unit price</th>
                            <th>Setting time</th>
                            <th>Unit time</th>
                            <th>Remaining time</th>
                            <th>Statu</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Tasklist as $Task)
                        <tr>
                            <td></td>
                            <td>{{ $Task->ORDER }}</td>
                            <td>#{{ $Task->id }} - {{ $Task->label }}</td>
                            <td>@if($Task->component_id ) {{ $Task->Component['LABEL'] }}@endif</td>
                            <td>@if($Task->methods_services_id ) {{ $Task->service['LABEL'] }}@endif</td>
                            <td>{{ $Task->qty }}</td>
                            <td>{{ $Task->qty_init }}</td>
                            <td>{{ $Task->qty_aviable }}</td>
                            <td>@if($Task->methods_units_id ) {{ $Task->Unit['LABEL'] }}@endif</td>
                            <td>{{ $Task->UNIT_COST }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->UNIT_PRICE }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->SETING_TIME }}</td>
                            <td>{{ $Task->UNIT_TIME }}</td>
                            <td>{{ $Task->REMAINING_TIME }}</td>
                            <td>
                                @if(1 == $Task->statu )   <span class="badge badge-info"> Open</span>@endif
                                @if(2 == $Task->statu )  <span class="badge badge-warning">Started</span>@endif
                                @if(3 == $Task->statu )  <span class="badge badge-success">In progress</span>@endif
                                @if(4 == $Task->statu )   <span class="badge badge-info"> Finished</span>@endif
                                @if(5 == $Task->statu )  <span class="badge badge-warning">Suspended</span>@endif
                                @if(6 == $Task->statu )  <span class="badge badge-success">To RFQ</span>@endif
                                @if(7 == $Task->statu )  <span class="badge badge-warning">RFQ in progress</span>@endif
                                @if(8 == $Task->statu )  <span class="badge badge-success">Outsourced</span>@endif
                                @if(9 == $Task->statu )  <span class="badge badge-success">Supplied</span>@endif
                            </td>
                            <td></td>
                        </tr>
                        @empty
                        <tr>
                            <th>No Lines</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Order</th>
                            <th>Sort</th>
                            <th>Description</th>
                            <th>Product</th>
                            <th>Service</th>
                            <th>Qty</th>
                            <th>Qty init</th>
                            <th>Qty aviable</th>
                            <th>Unit</th>
                            <th>Unit cost</th>
                            <th>Unit price</th>
                            <th>Setting time</th>
                            <th>Unit time</th>
                            <th>Remaining time</th>
                            <th>Statu</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
