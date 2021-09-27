

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

                @if($updateLines)
                    @include('livewire.quote-line-update')
                @else
                    @include('livewire.quote-line-create')
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="example1" class="table table-bordered table-striped">
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
                                <th>Statu</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            @forelse ($QuoteLineslist as $QuoteLine)
                            <tr>
                              <td>{{ $QuoteLine->ORDRE }}</td>
                              <td>{{ $QuoteLine->CODE }}</td>
                              <td>@if(1 == $QuoteLine->product_id ) {{ $QuoteLine->Product['LABEL'] }}@endif</td>
                              <td>{{ $QuoteLine->LABEL }}</td>
                              <td>{{ $QuoteLine->qty }}</td>
                              <td>{{ $QuoteLine->Unit['LABEL'] }}</td>
                              <td>{{ $QuoteLine->selling_price }}</td>
                              <td>{{ $QuoteLine->discount }}</td>
                              <td>{{ $QuoteLine->VAT['RATE'] }}</td>
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
                                <button wire:click="edit({{$QuoteLine->id}})" class="btn btn-sm btn-outline-danger py-0">Edit</button> | 
                                <button wire:click="destroy({{$QuoteLine->id}})" class="btn btn-sm btn-outline-danger py-0">Delete</button>
                              </td>
                            </tr>
                            @empty
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
                                <th>Statu</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>



<script>
    function deleteCategory(id){
        if(confirm("Are you sure to delete this record?"))
            window.livewire.emit('deleteCategory',id);
    }
</script>
</div>
