
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
        <div class="card">
            <div class="card-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                    </div>
                    <input type="text" class="form-control" wire:model="search" placeholder="Search Task">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Sort</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">Label @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>Product</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('methods_units_id')" role="button" href="#">Service @include('include.sort-icon', ['field' => 'methods_units_id'])</a>
                            </th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Unit cost</th>
                            <th>Unit price</th>
                            <th>Setting time</th>
                            <th>Unit time</th>
                            <th>Statu</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Tasklist as $Task)
                        <tr @if($Task->methods_services_id ) style="color: {{ $Task->service['color'] }};" @endif > 
                            <td>
                                <x-OrderButton id="{{ $Task->OrderLines->orders_id }}" code="{{ $Task->OrderLines->order->code }}"  />
                            </td>
                            <td>{{ $Task->ORDER }}</td>
                            <td>#{{ $Task->id }} - {{ $Task->label }}</td>
                            <td>@if($Task->component_id ) {{ $Task->Component['label'] }}@endif</td>
                            <td>@if($Task->methods_services_id ) {{ $Task->service['label'] }}@endif</td>
                            <td>{{ $Task->qty }}</td>
                            <td>@if($Task->methods_units_id ) {{ $Task->Unit['label'] }}@endif</td>
                            <td>{{ $Task->unit_cost }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->unit_price }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->seting_time }}</td>
                            <td>{{ $Task->unit_time }}</td>
                            <td>{{ $Task->status['title'] }}</td>
                            <td></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="17">
                                <div class="flex justify-center items-center">
                                    <i class="fa fa-lg fa-fw  fa-inbox"></i><span class="font-medium py-8 text-cool-gray-400 text-x1"> No task found ...</span>
                                </div>
                            </td>
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
                            <th>Unit</th>
                            <th>Unit cost</th>
                            <th>Unit price</th>
                            <th>Setting time</th>
                            <th>Unit time</th>
                            <th>Statu</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.row -->
        </div>
    </div>