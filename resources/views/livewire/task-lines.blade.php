
    <div>
        @include('include.alert-result')
        <div class="card">
            <div class="card-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
                    </div>
                    <input type="text" class="form-control" wire:model="search" placeholder="Search Task">
                </div>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order</th>
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
                            <th>Total time</th>
                            <th>Progress</th>
                            <th>Statu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($Tasklist as $Task)
                        <tr > 
                            <td>
                                @if($Task->OrderLines ?? null)
                                    <x-OrderButton id="{{ $Task->OrderLines->orders_id }}" code="{{ $Task->OrderLines->order->code }}"  />
                                @else
                                    Generic
                                @endif
                            </td>
                            <td><a href="{{ route('production.task.statu.id', ['id' => $Task->id]) }}" class="btn btn-sm btn-success">View </a> #{{ $Task->id }} - {{ $Task->label }}</td>
                            <td>@if($Task->component_id ) {{ $Task->Component['label'] }}@endif</td>
                            <td @if($Task->methods_services_id ) style="color: {{ $Task->service['color'] }};" @endif >@if($Task->methods_services_id ) {{ $Task->service['label'] }}@endif</td>
                            <td>{{ $Task->qty }}</td>
                            <td>@if($Task->methods_units_id ) {{ $Task->Unit['label'] }}@endif</td>
                            <td>{{ $Task->unit_cost }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->unit_price }} {{ $Factory->curency }}</td>
                            <td>{{ $Task->TotalTime() }} h</td>
                            <td>
                                @if($Task->progress() > 100 )
                                    <x-adminlte-progress theme="teal" value="100" with-label animated/>
                                @else
                                    <x-adminlte-progress theme="teal" value="{{ $Task->progress() }}" with-label animated/>
                                @endif
                            </td>
                            <td>{{ $Task->status['title'] }}</td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="17" text="No task found ..."  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Order</th>
                            <th>Description</th>
                            <th>Product</th>
                            <th>Service</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Unit cost</th>
                            <th>Unit price</th>
                            <th>Total Time</th>
                            <th>Progress</th>
                            <th>Statu</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.row -->
        </div>
    </div>