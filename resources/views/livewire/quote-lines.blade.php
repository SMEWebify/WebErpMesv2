
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')
            @if($QuoteStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="updateQuoteLine">
                            <input type="hidden" wire:model="quote_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeQuoteLine">
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
                                
                                <div class="btn-group btn-group-sm">
                                    <a href="#" wire:click="upQuoteLine({{ $QuoteLine->id }})" class="btn btn-secondary"><i class="fas fa-sort-up"></i></a>
                                    <span class="btn btn-info">{{ $QuoteLine->ordre }}</span>
                                    <a href="#" wire:click="downQuoteLine({{ $QuoteLine->id }})" class="btn btn-primary"><i class="fas fa-sort-down"></i></a>
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
                                    <div class="btn-group btn-group-sm">
                                        <!-- Button Modal -->
                                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#QuoteLine{{ $QuoteLine->id }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <!-- Modal {{ $QuoteLine->id }} -->
                                        <x-adminlte-modal id="QuoteLine{{ $QuoteLine->id }}" title="Update detail information for {{ $QuoteLine->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                                            <form method="POST" action="{{ route('quotes.update.detail.line', ['idQuote'=>  $QuoteLine->quotes_id, 'id' => $QuoteLine->QuoteLineDetails->id]) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->material }}" name="material" id="material" placeholder="Material">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->thickness }}" name="thickness" id="thickness" placeholder="Thickness" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->weight }}" name="weight" id="weight" placeholder="Weight" step=".001">
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label for="x_size">X</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $QuoteLine->QuoteLineDetails->x_size }}" name="x_size" id="x_size" placeholder="X size" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <label for="y_size">Y</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $QuoteLine->QuoteLineDetails->y_size }}"  name="y_size" id="y_size" placeholder="Y size" step=".001">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <label for="z_size">Z</label>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                            </div>
                                                            <input type="number" class="form-control" value="{{  $QuoteLine->QuoteLineDetails->z_size }}" name="z_size" id="z_size" placeholder="Z size" step=".001">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control"  value="{{ $QuoteLine->QuoteLineDetails->x_oversize }}" name="x_oversize" id="x_oversize" placeholder="X oversize" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->y_oversize }}" name="y_oversize" id="y_oversize" placeholder="Y oversize" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->z_oversize }}" name="z_oversize" id="z_oversize" placeholder="Z oversize" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->diameter }}" name="diameter" id="diameter" placeholder="Diameter" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize" placeholder="Diameter_oversize" step=".001">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                                </div>
                                                                <input type="number" class="form-control" value="{{ $QuoteLine->QuoteLineDetails->material_loss_rate }}" name="material_loss_rate" id="material_loss_rate" placeholder="Material loss rate" step=".001">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                                                </div>
                                            </form>
                                            <div class="card-body">
                                                <form action="{{ route('quotes.update.detail.picture', ['idQuote'=>  $QuoteLine->quotes_id, 'id' => $QuoteLine->QuoteLineDetails->id]) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <label for="picture">Picture file</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-image"></i></span>
                                                        </div>
                                                        <div class="custom-file">
                                                            <input type="hidden" name="id" value="{{ $QuoteLine->id }}">
                                                            <input type="file" class="custom-file-input" name="picture" id="picture">
                                                            <label class="custom-file-label" for="picture">Choose file</label>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <button type="submit" class="btn btn-success">Upload</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-adminlte-modal>
                                    </div>
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
                                        <a href="{{ route('task.manage', ['id_type'=> 'quote_lines_id', 'id_page'=>  $QuoteLine->quotes_id, 'id_line' => $QuoteLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> Tasks {{  $QuoteLine->getTaskCountAttribute() }}</span></a></button>
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
