<div class="card-body">
    <div class="card">
        @include('include.search-card')
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('quotes_id')" role="button" href="#">Quote @include('include.sort-icon', ['field' => 'quotes_id'])</a>
                        </th>
                        <th>Sort</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">Code @include('include.sort-icon', ['field' => 'code'])</a>
                        </th>
                        <th>Product</th>
                        <th>
                            <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
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
                    @forelse ($QuoteLineslist as $QuoteLine)
                    <tr>
                        <td>
                            <x-QuoteButton id="{{ $QuoteLine->quotes_id }}" code="{{ $QuoteLine->quote['code'] }}"  />
                        </td>
                        <td>{{ $QuoteLine->ordre }}</td>
                        <td>{{ $QuoteLine->code }}</td>
                        <td>
                            @if($QuoteLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $QuoteLine->product_id])}}" />@endif
                        </td>
                        <td>{{ $QuoteLine->label }}</td>
                        <td>{{ $QuoteLine->qty }}</td>
                        <td>{{ $QuoteLine->Unit['label'] }}</td>
                        <td>{{ $QuoteLine->selling_price }}</td>
                        <td>{{ $QuoteLine->discount }}</td>
                        <td>{{ $QuoteLine->VAT['label'] }}</td>
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
                            <x-ButtonTextView route="{{ route('quotes.show', ['id' => $QuoteLine->quotes_id])}}" />
                        </td>
                    </tr>
                    @empty
                        <x-EmptyDataLine col="13" text="No quotes lines found ..."  />
                    @endforelse
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