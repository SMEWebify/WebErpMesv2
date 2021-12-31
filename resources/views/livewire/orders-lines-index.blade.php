<div class="card-body">
    <div class="card">
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
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('orders_id')" role="button" href="#">Quote @include('include.sort-icon', ['field' => 'orders_id'])</a>
                        </th>
                        <th>Sort</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('CODE')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'CODE'])</a>
                        </th>
                        <th>Product</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('LABEL')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'LABEL'])</a>
                        </th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Selling price</th>
                        <th>Discount</th>
                        <th>VAT type</th>
                        <th>Delivery date</th>
                        <th>Statu</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($OrderLineslist as $OrderLine)
                    <tr>
                        <td>{{ $OrderLine->order['CODE'] }}</td>
                        <td>{{ $OrderLine->ORDRE }}</td>
                        <td>{{ $OrderLine->CODE }}</td>
                        <td>@if(1 == $OrderLine->product_id ) {{ $OrderLine->Product['LABEL'] }}@endif</td>
                        <td>{{ $OrderLine->LABEL }}</td>
                        <td>{{ $OrderLine->qty }}</td>
                        <td>{{ $OrderLine->Unit['LABEL'] }}</td>
                        <td>{{ $OrderLine->selling_price }}</td>
                        <td>{{ $OrderLine->discount }}</td>
                        <td>{{ $OrderLine->VAT['LABEL'] }}</td>
                        <td>{{ $OrderLine->delivery_date }}</td>
                        <td>
                            @if(1 == $OrderLine->statu )  <span class="badge badge-info"> None (no task created for that sales order line)</span>@endif
                            @if(2 == $OrderLine->statu )  <span class="badge badge-warning">Created</span>@endif
                            @if(3 == $OrderLine->statu )  <span class="badge badge-success">In progress</span>@endif
                            @if(4 == $OrderLine->statu )  <span class="badge badge-danger">Finished (all the tasks are finished)</span>@endif
                        </td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $OrderLine->orders_id])}}">
                            <i class="fas fa-folder"></i>
                            View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13">
                            <div class="flex justify-center items-center">
                                <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No order line found ...</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>Order</th>
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
                        <th>Statu</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.row -->
        {{ $OrderLineslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>