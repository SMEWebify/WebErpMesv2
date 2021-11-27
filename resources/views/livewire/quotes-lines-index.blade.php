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
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('quotes_id')" role="button" href="#">Quote @include('include.sort-icon', ['field' => 'quotes_id'])</a>
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
                    @foreach ($QuoteLineslist as $QuoteLine)
                    <tr>
                        <td>{{ $QuoteLine->quote['CODE'] }}</td>
                        <td>{{ $QuoteLine->ORDRE }}</td>
                        <td>{{ $QuoteLine->CODE }}</td>
                        <td>@if(1 == $QuoteLine->product_id ) {{ $QuoteLine->Product['LABEL'] }}@endif</td>
                        <td>{{ $QuoteLine->LABEL }}</td>
                        <td>{{ $QuoteLine->qty }}</td>
                        <td>{{ $QuoteLine->Unit['LABEL'] }}</td>
                        <td>{{ $QuoteLine->selling_price }}</td>
                        <td>{{ $QuoteLine->discount }}</td>
                        <td>{{ $QuoteLine->VAT['LABEL'] }}</td>
                        <td>{{ $QuoteLine->delivery_date }}</td>
                        <td>
                            @if(1 == $QuoteLine->statu )   <span class="badge badge-info"> Open</span>@endif
                            @if(2 == $QuoteLine->statu )  <span class="badge badge-warning">Send</span>@endif
                            @if(3 == $QuoteLine->statu )  <span class="badge badge-success">Win</span>@endif
                            @if(4 == $QuoteLine->statu )  <span class="badge badge-danger">Lost</span>@endif
                            @if(5 == $QuoteLine->statu )  <span class="badge badge-secondary">Closed</span>@endif
                            @if(6 == $QuoteLine->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                        </td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('quote.show', ['id' => $QuoteLine->quotes_id])}}">
                            <i class="fas fa-folder"></i>
                            View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Quote</th>
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
        {{ $QuoteLineslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>