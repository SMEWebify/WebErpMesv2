
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

            @if($QuoteStatu == 1)
                @if($updateLines)
                    @include('livewire.form.quote-line-update')
                @else
                    @include('livewire.form.quote-line-create')
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
                <table  class="table">
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
                            <td>
                                {{ $QuoteLine->ORDRE }} - 
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="upQuoteLine({{ $QuoteLine->id }})" class="btn btn-secondary"><i class="fa fa-lg fa-fw  fa-sort-amount-down"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="downQuoteLine({{ $QuoteLine->id }})" class="btn btn-primary"><i class="fa fa-lg fa-fw  fa-sort-amount-up-alt"></i></a>
                                </div>
                            </td>
                            <td>{{ $QuoteLine->code }}</td>
                            <td>
                                @if($QuoteLine->product_id ) {{ $QuoteLine->Product['label'] }}@endif
                            </td>
                            <td>{{ $QuoteLine->label }}</td>
                            <td>{{ $QuoteLine->qty }}</td>
                            <td>{{ $QuoteLine->Unit['label'] }}</td>
                            <td>{{ $QuoteLine->selling_price }} {{ $Factory->curency }}</td>
                            <td>{{ $QuoteLine->discount }} %</td>
                            <td>{{ $QuoteLine->VAT['rate'] }} %</td>
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
                                @if($QuoteStatu == 1)
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="editQuoteLine({{$QuoteLine->id}})" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="destroyQuoteLine({{$QuoteLine->id}})" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
                                </div>
                                @endif
                                <!-- Modal -->
                                <div class="modal fade" id="MainProcessModal{{$QuoteLine->id}}" tabindex="-1" role="dialog" aria-labelledby="MainProcessModalTitle{{$QuoteLine->id}}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                            <h5 class="modal-title" id="MainProcessModalTitle{{$QuoteLine->id}}">Add line to BOM</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                            </div>
                                            <div class="modal-body">
                                                @include('include.Main-procces', ['route' => route('task.store', ['id' => $QuoteLine->quotes_id]),'id_page' => $QuoteLine->quotes_id, 'id_type' => 'quote_lines_id', 'id_line' => $QuoteLine->id, 'status_id'=>$status_id['id'] , 'task' => $QuoteLine->Task])
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
                                    <a href="#" data-toggle="modal" data-target="#MainProcessModal{{$QuoteLine->id}}"  class="btn btn-success"><i class="fa fa-lg fa-fw  fas fa-list"></i></a>
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
                            <th>Statu</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
