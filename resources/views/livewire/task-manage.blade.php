<div>
    <div class="card-body">
    @if($statu == 1)
        @include('include.alert-result')
        @if($updateLines)
        <form wire:submit.prevent="updateTask">
        @else
        <form wire:submit.prevent="storeTask({{ $Line->id }})">
        @endif
            <div class="card card-body">
                <div class="row">
                    <div class="col-2">
                        <label for="companies_id">Task type</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <select class="form-control" wire:click.prevent="ChangeTaskType()" wire:model="TaskType" name="TaskType" id="TaskType">
                                <option value="">Select your task Type</option>
                                <option value="TechCut">Technical Cut</option>
                                <option value="BOM">BOM</option>
                            </select>
                        </div>
                        @error('document_type') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="ordre">Sort order</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            </div>
                            <input type="number" class="form-control @error('ordre') is-invalid @enderror" name="ordre" id="ordre" placeholder="Order" min="0" wire:model="ordre">
                            
                            <input type="hidden" name="{{ $idType }}" value="{{ $Line->id   }}">
                            <input type="hidden" name="qty"  id="qty"  value="{{ $Line->qty  }}" value=".001">
                        </div>
                        @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="methods_services_id">Services</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                            </div>
                            <select class="form-control @error('methods_services_id') is-invalid @enderror" wire:click.prevent="ChangeCodelabel()" name="methods_services_id" id="methods_services_id" wire:model="methods_services_id">
                            <option>Select Services</option>
                                @foreach ($ServicesSelect as $item)
                                <option value="{{ $item->id }}-{{ $item->type }}" data-txt="{{ $item->label }}">{{ $item->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('methods_services_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        <label for="label">Label</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tags"></i></span>
                            </div>
                            <input type="text" class="form-control @error('label') is-invalid @enderror"  name="label"  id="LABEL_TechnicalCut" placeholder="Label" wire:model="label">
                        </div>
                        @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        @if($TaskType == 'BOM') 
                        <label for="component_id">Component</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            </div>
                            <select class="form-control @error('component_id') is-invalid @enderror" name="component_id" id="component_id"  wire:model="component_id" >
                                <option>Select Component</option>
                                @foreach ($ProductSelect as $item)
                                <option value="{{ $item->id }}" class="{{ $item->methods_services_id }}">{{ $item->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('component_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @endif 
                    </div>
                </div>
                <div class="row">
                    <div class="col-2">
                    </div>
                    <div class="col-2">
                        @if($TaskType == 'TechCut')
                        <label for="seting_time">Setting time</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                            </div>
                            <input type="number" class="form-control @error('seting_time') is-invalid @enderror" name="seting_time"  id="seting_time" placeholder="Setting time" value="0" step=".001"  min="0"  wire:model="seting_time" >
                        </div>
                        @error('seting_time') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @else 
                        <label for="qty">Quantity</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-times"></i></span>
                            </div>
                            <input type="number" class="form-control @error('qty') is-invalid @enderror" name="qty"  id="qty" value="{{ $Line->qty  }}" placeholder="Quantity" step=".001"  min="0" wire:model="qty">
                        </div>
                        @error('qty') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @endif
                    </div>
                    <div class="col-2">
                        @if($TaskType == 'TechCut')
                        <label for="unit_time">Unit time</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                            </div>
                            <input type="number" class="form-control @error('unit_time') is-invalid @enderror" name="unit_time"  id="unit_time" placeholder="Unit time" value="0" step=".001"  min="0" wire:model="unit_time" >
                        </div>
                        @error('unit_time') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        @endif
                    </div>
                    <div class="col-2">
                        <label for="unit_cost">Unit cost</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('unit_cost') is-invalid @enderror" name="unit_cost"  id="unit_cost" placeholder="Unit cost" value="0" step=".001" min="0" wire:model="unit_cost">
                        </div>
                    </div>
                    <div class="col-2">
                        <label for="unit_price">Unit price</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ $Factory->curency }}</span>
                            </div>
                            <input type="number" class="form-control @error('unit_price') is-invalid @enderror" name="unit_price"  id="unit_price" placeholder="Unit time" value="0" step=".001" min="0" wire:model="unit_price">
                        </div>
                        @error('unit_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-2">
                        @if($TaskType == 'BOM' or $TaskType == 'TechCut')
                            @if($updateLines)
                            <button type="Submit" class="btn btn-warning">Update</button>
                            @else
                            <x-adminlte-button class="btn-flat" type="submit" label="Add Task" theme="success" icon="fas fa-lg fa-save"/>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif

    @if($Line->id ?? null)
    <div class="card-body">
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Technical cut</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sort order</th>
                            <th>Label</th>
                            <th>Service</th>
                            <th>Setting time</th>
                            <th>Unit time</th>
                            <th>Unit cost</th>
                            <th>Unit price</th>
                            <th>Statu</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Line->TechnicalCut as $TechLine)
                        <tr>
                            <td>{{ $TechLine->ordre }}</td>
                            <td>{{ $TechLine->label }}</td>
                            <td>{{ $TechLine->service['label'] }}</td>
                            <td>{{ $TechLine->seting_time }}</td>
                            <td>{{ $TechLine->unit_time }}</td>
                            <td>{{ $TechLine->unit_cost }} {{ $Factory->curency }}</td>
                            <td>{{ $TechLine->unit_price }} {{ $Factory->curency }}</td>
                            <td>
                            @if($TechLine->order_lines_id)
                                {{ $TechLine->status['title'] }}
                            @else
                                Not for this page
                            @endif
                            </td>
                            <td class=" py-0 align-middle">
                                <div class="input-group-prepend">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <div class="dropdown-menu">
                                        <a href="#" class="dropdown-item " wire:click="duplicateLine({{$TechLine->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> Copy line</span></a>
                                        <a href="#" class="dropdown-item" wire:click="editTaskLine({{$TechLine->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> Edit line</span></a>
                                        <a href="#" class="dropdown-item" wire:click="destroyTaskLine({{$TechLine->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> Delete line</span></a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="9" text="No line found ..."  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                        <th>Sort order</th>
                        <th>Label</th>
                        <th>Service</th>
                        <th>Setting time</th>
                        <th>Unit time</th>
                        <th>Unit cost</th>
                        <th>Unit price</th>
                        <th>Statu</th>
                        <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
            
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Bill of materials</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                    <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sort order</th>
                        <th>Label</th>
                        <th>Service</th>
                        <th>Component</th>
                        <th>Quantity</th>
                        <th>Unit cost</th>
                        <th>Unit price</th>
                        <th>Statu</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                        @forelse($Line->BOM as $BOMline)
                        <tr>
                        <td>{{ $BOMline->ordre }}</td>
                        <td>{{ $BOMline->label }}</td>
                        <td>{{ $BOMline->service['label'] }}</td>
                        <td>{{ $BOMline->Component['code'] }}</td>
                        <td>{{ $BOMline->qty }}</td>
                        <td>{{ $BOMline->unit_cost }} {{ $Factory->curency }}</td>
                        <td>{{ $BOMline->unit_price }} {{ $Factory->curency }}</td>
                        <td>
                            @if($BOMline->order_lines_id)
                            {{ $BOMline->status['title'] }}
                            @else
                            Not for this page
                            @endif
                        </td>
                        <td class=" py-0 align-middle">
                            <div class="input-group-prepend">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <div class="dropdown-menu">
                                    <a href="#" class="dropdown-item " wire:click="duplicateLine({{$BOMline->id}})" ><span class="text-info"><i class="fa fa-light fa-fw  fa-copy"></i> Copy line</span></a>
                                    <a href="#" class="dropdown-item" wire:click="editTaskLine({{$BOMline->id}})"><span class="text-primary"><i class="fa fa-lg fa-fw  fa-edit"></i> Edit line</span></a>
                                    <a href="#" class="dropdown-item" wire:click="destroyTaskLine({{$BOMline->id}})" ><span class="text-danger"><i class="fa fa-lg fa-fw fa-trash"></i> Delete line</span></a>
                                </div>
                            </div>
                        </td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="9" text="No line found ..."  />
                        @endforelse
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Sort order</th>
                        <th>Label</th>
                        <th>Service</th>
                        <th>Component</th>
                        <th>Quantity</th>
                        <th>Unit cost</th>
                        <th>Unit price</th>
                        <th>Statu</th>
                        <th>Action</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
    </div>
    @endif
</div>
