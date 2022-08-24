
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')
            @if($QuoteStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="updateQuoteLine">
                    <div class="row">
                        <div class="col-2">
                            <input type="hidden" wire:model="quote_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeQuoteLine">
                    <div class="row">
                        <div class="col-2">
                            <input type="hidden"  name="quotes_id"  id="quotes_id" value="1" wire:model="quotes_id" >
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
                            <th>Statu</th>
                            <th colspan="2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($QuoteLineslist as $QuoteLine)
                        <tr>
                            <td>
                                {{ $QuoteLine->ordre }} - 
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="upQuoteLine({{ $QuoteLine->id }})" class="btn btn-secondary"><i class="fa fa-lg fa-fw  fa-sort-amount-down"></i></a>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="downQuoteLine({{ $QuoteLine->id }})" class="btn btn-primary"><i class="fa fa-lg fa-fw  fa-sort-amount-up-alt"></i></a>
                                </div>
                            </td>
                            <td>{{ $QuoteLine->code }}</td>
                            <td>
                                @if($QuoteLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $QuoteLine->product_id])}}" />@endif
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
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <div class="dropdown-menu">
                                            @if($QuoteStatu == 1)
                                            <a href="#" class="dropdown-item " wire:click="duplicateLine({{$QuoteLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> Copy line</span></a>
                                            <a href="#" class="dropdown-item" wire:click="editQuoteLine({{$QuoteLine->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> Edit line</span></a>
                                            <a href="#" class="dropdown-item" wire:click="destroyQuoteLine({{$QuoteLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> Delete line</span></a>
                                            @if($QuoteLine->product_id )
                                            <a href="#" class="dropdown-item" wire:click="breakDown({{$QuoteLine->id}})"><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i>Break down the article task</span></a>
                                            @endif
                                            @else
                                                <p class="dropdown-item "><span class="text-info">Quote curently not open</span></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('task.manage', ['id_type'=> 'quote_lines_id', 'id_page'=>  $QuoteLine->quotes_id, 'id_line' => $QuoteLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> Tasks ({{  $QuoteLine->getTaskCountAttribute() }})</span></a></button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" value="{{ $QuoteLine->id }}" wire:model="data.{{ $QuoteLine->id }}.quote_line_id" id="data.{{ $QuoteLine->id }}.quote_line_id"  type="checkbox">
                                    <label for="data.{{ $QuoteLine->id }}.quote_line_id" class="custom-control-label">+</label>
                                </div>
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="13" text="No lines found ..."  />
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
                            <th></th>
                            <th >
                                <a class="btn btn-primary btn-sm" wire:click="storeOrder({{ $QuoteId }})" href="#">
                                    <i class="fas fa-folder"></i>
                                    New order
                                </a>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
