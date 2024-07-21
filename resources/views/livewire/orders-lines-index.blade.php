<div>
    <div class="card">
        @include('include.alert-result')
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    @include('include.search-card')
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('orders_id')" role="button" href="#">{{__('general_content.order_trans_key') }}  @include('include.sort-icon', ['field' => 'orders_id'])</a>
                            </th>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('code')" role="button" href="#">{{__('general_content.id_trans_key') }} @include('include.sort-icon', ['field' => 'code'])</a>
                            </th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('label')" role="button" href="#">{{__('general_content.label_trans_key') }} @include('include.sort-icon', ['field' => 'label'])</a>
                            </th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{ __('general_content.tasks_status_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_status_trans_key') }}</th>
                            <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                            <th>{{ __('general_content.task_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($OrderLineslist as $OrderLine)
                        <tr>
                            <td>
                                <x-OrderButton id="{{ $OrderLine->orders_id }}" code="{{ $OrderLine->order['code'] }}"  />
                            </td>
                            <td>{{ $OrderLine->ordre }}</td>
                            <td>{{ $OrderLine->code }}</td>
                            <td>
                                @if($OrderLine->product_id ) <x-ButtonTextView route="{{ route('products.show', ['id' => $OrderLine->product_id])}}" />@endif
                            </td>
                            <td>{{ $OrderLine->label }}</td>
                            <td> {{ $OrderLine->qty }}</td>
                            <td>{{ $OrderLine->Unit['label'] }}</td>
                            <td>{{ $OrderLine->selling_price }}</td>
                            <td>{{ $OrderLine->discount }}</td>
                            <td>{{ $OrderLine->VAT['label'] }}</td>
                            <td>{{ $OrderLine->delivery_date }}</td>
                            <td>
                                @if(1 == $OrderLine->tasks_status )  <span class="badge badge-info">{{ __('general_content.no_task_trans_key') }}</span>@endif
                                @if(2 == $OrderLine->tasks_status )  
                                    <span class="badge badge-warning">{{ __('general_content.created_trans_key') }}</span> 
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressTaskAttribute() }}" with-label animated/>
                                @endif
                                @if(3 == $OrderLine->tasks_status )  
                                    <span class="badge badge-success">{{ __('general_content.in_progress_trans_key') }}</span>
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressTaskAttribute() }}" with-label animated/>
                                @endif
                                @if(4 == $OrderLine->tasks_status )  
                                    <span class="badge badge-danger">{{ __('general_content.finished_task_trans_key') }}</span>
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressTaskAttribute() }}" with-label animated/>
                                @endif
                            </td>
                            <td>
                                @if($OrderLine->order->type == 2)
                                    @if(1 == $OrderLine->delivery_status )  <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>@endif
                                    @if(2 == $OrderLine->delivery_status )  <span class="badge badge-warning">{{ __('general_content.partly_stored_trans_key') }}</span>@endif
                                    @if(3 == $OrderLine->delivery_status )  <span class="badge badge-success">{{ __('general_content.stock_trans_key') }}</span>@endif
                                @else
                                    @if(1 == $OrderLine->delivery_status )  <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>@endif
                                    @if(2 == $OrderLine->delivery_status )  
                                    <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $OrderLine->id }}"><span class="badge badge-warning">{{ __('general_content.partly_delivered_trans_key') }} ({{ $OrderLine->delivered_qty }} )</span></a>
                                    @endif
                                    @if(3 == $OrderLine->delivery_status )  
                                    <a href="#" data-toggle="modal" data-target="#modalDeliveryFor{{ $OrderLine->id }}"><span class="badge badge-success">{{ __('general_content.delivered_trans_key') }} ({{ $OrderLine->delivered_qty }} )</span></a>
                                    @endif
                                    @if(4 == $OrderLine->delivery_status )  <span class="badge badge-primary" >{{ __('general_content.delivered_without_dn_trans_key') }} ({{ $OrderLine->delivered_qty }} )</span>@endif
                                
                                    {{-- Modal for delivery detail --}}
                                    <x-adminlte-modal id="modalDeliveryFor{{ $OrderLine->id }}" title="{{__('general_content.deliverys_notes_list_trans_key') }}" theme="info"
                                        icon="fas fa-bolt" size='lg' disable-animations>
                                        <ul>
                                            @foreach($OrderLine->DeliveryLines as $deliveryLine)
                                                <li>
                                                    {{ __('general_content.delivery_notes_trans_key') }}: {{ $deliveryLine->delivery->code }} <br>
                                                    {{ __('general_content.qty_trans_key') }} : {{ $deliveryLine->qty }} <br>
                                                    {{__('general_content.created_at_trans_key') }} : {{ $deliveryLine->GetPrettyCreatedAttribute() }} <br>
                                                    <x-ButtonTextView route="{{ route('deliverys.show', ['id' => $deliveryLine->deliverys_id])}}" />
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-adminlte-modal>
                                
                                @endif
                                @if(1 != $OrderLine->delivery_status )
                                    <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressDeleveryAttribute() }}" with-label animated/>
                                @endif
                            </td>
                            <td>
                                @if($OrderLine->order->type == 2)
                                    -
                                @else
                                    @if(1 == $OrderLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.not_invoiced_trans_key') }}</span>@endif
                                    @if(2 == $OrderLine->invoice_status )
                                    <a href="#" data-toggle="modal" data-target="#modalInvoiceFor{{ $OrderLine->id }}"><span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }} ({{ $OrderLine->invoiced_qty }} )</span></a>
                                    @endif
                                    @if(3 == $OrderLine->invoice_status )
                                    <a href="#" data-toggle="modal" data-target="#modalInvoiceFor{{ $OrderLine->id }}"><span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }} ({{ $OrderLine->invoiced_qty }} )</span></a>
                                    @endif

                                    {{-- Modal for delivery detail --}}
                                    <x-adminlte-modal id="modalInvoiceFor{{ $OrderLine->id }}" title="{{__('general_content.invoices_list_trans_key') }}" theme="info"
                                        icon="fas fa-bolt" size='lg' disable-animations>
                                        <ul>
                                            @foreach($OrderLine->InvoiceLines as $InvoiceLine)
                                                <li>
                                                    {{ __('general_content.invoices_trans_key') }} : {{ $InvoiceLine->invoice->code }} <br>
                                                    {{ __('general_content.qty_trans_key') }} : {{ $InvoiceLine->qty }} <br>
                                                    {{__('general_content.created_at_trans_key') }} : {{ $InvoiceLine->GetPrettyCreatedAttribute() }} <br>
                                                    <x-ButtonTextView route="{{ route('invoices.show', ['id' => $InvoiceLine->invoices_id])}}" />
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-adminlte-modal>
                                
                                    @if(1 != $OrderLine->invoice_status )
                                        <x-adminlte-progress theme="teal" value="{{ $OrderLine->getAveragePercentProgressInvoiceAttribute() }}" with-label animated/>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <!-- Button Modal -->
                                    <button type="button" class="btn bg-warning" data-toggle="modal" data-target="#OrderLineTasks{{ $OrderLine->id }}">
                                        <i class="fa fa-lg fa-fw  fas fa-list"></i>
                                    </button>
                                    <!-- Modal {{ $OrderLine->id }} -->
                                    <x-adminlte-modal id="OrderLineTasks{{ $OrderLine->id }}" title="Task detail for {{ $OrderLine->label }}" theme="warning" icon="fa fa-pen" size='lg' disable-animations>
                                        <div class="card-body">
                                            <div class="row">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('general_content.order_trans_key') }}</th>
                                                            <th>{{ __('general_content.label_trans_key') }}</th>
                                                            <th>{{ __('general_content.service_trans_key') }}</th>
                                                            <th>{{ __('general_content.total_time_trans_key') }}</th>
                                                            <th>{{ __('general_content.qty_trans_key') }}</th>
                                                            <th>{{ __('general_content.cost_trans_key') }}</th>
                                                            <th>{{ __('general_content.margin_trans_key') }}</th>
                                                            <th>{{ __('general_content.price_trans_key') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ( $OrderLine->Task as $Task)
                                                        <tr>
                                                            <td>{{ $Task->ordre }}</td>
                                                            <td>{{ $Task->label }}</td>
                                                            <td @if($Task->methods_services_id ) style="background-color: {{ $Task->service['color'] }};" @endif >
                                                                @if($Task->methods_services_id )
                                                                    @if( $Task->service['picture'])
                                                                        <p data-toggle="tooltip" data-html="true" title="<img alt='Service' class='profile-user-img img-fluid img-circle' src='{{ asset('/images/methods/'. $Task->service['picture']) }}'>">
                                                                            <span>{{ $Task->service['label'] }}</span>
                                                                        </p>
                                                                    @else
                                                                        {{ $Task->service['label'] }}
                                                                    @endif
                                                                @endif
                                                            </td>
                                                            <td>{{ $Task->TotalTime() }} h</td>
                                                            <td>{{ $Task->qty }}</td>
                                                            <td>{{ $Task->unit_cost }} {{ $Factory->curency }}</td>
                                                            <td>{{ $Task->Margin() }} %</td>
                                                            <td>{{ $Task->unit_price }} {{ $Factory->curency }}</td>
                                                        </tr>
                                                        @empty
                                                        <x-EmptyDataLine col="12" text="{{ __('general_content.no_data_trans_key') }}"  />
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <a class="btn btn-info btn-sm" href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}">
                                                <i class="fas fa-folder"></i>
                                                {{ __('general_content.view_trans_key') }}
                                            </a>
                                        </div>
                                    </x-adminlte-modal>
                                    <a href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $OrderLine->getAllTaskCountAttribute() }}</span></a>
                                </div>
                            </td>
                            <td>
                                <x-ButtonTextView route="{{ route('orders.show', ['id' => $OrderLine->orders_id])}}" />
                            </td>
                        </tr>
                        @empty
                            <x-EmptyDataLine col="13" text=" {{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.sort_trans_key') }}</th>
                            <th>{{ __('general_content.external_id_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.description_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.unit_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{ __('general_content.discount_trans_key') }}</th>
                            <th>{{ __('general_content.vat_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                            <th>{{ __('general_content.tasks_status_trans_key') }}</th>
                            <th>{{ __('general_content.delivery_status_trans_key') }}</th>
                            <th>{{ __('general_content.invoice_status_trans_key') }}</th>
                            <th>{{ __('general_content.task_trans_key') }}</th>
                            <th>{{__('general_content.action_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- /.row -->
            {{ $OrderLineslist->links() }}
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
<!-- /.div -->
</div>
