<div>
    <div class="card">
        @include('include.alert-result')
        <div class="card-body">
            <div class="row">
                @include('include.search-card')
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a class="btn btn-secondary" wire:click.prevent="sortBy('orders_id')" role="button" href="#">Order @include('include.sort-icon', ['field' => 'orders_id'])</a>
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
                            <td>
                                <a href="#" class="btn btn-primary btn-sm" data-toggle="tooltip" title="{{ __('general_content.delivered_trans_key') }} qty : {{ $OrderLine->delivered_qty }} <br /> Invoiced qty : {{ $OrderLine->invoiced_qty }}">{{ $OrderLine->qty }}</a>
                            </td>
                            <td>{{ $OrderLine->Unit['label'] }}</td>
                            <td>{{ $OrderLine->selling_price }}</td>
                            <td>{{ $OrderLine->discount }}</td>
                            <td>{{ $OrderLine->VAT['label'] }}</td>
                            <td>{{ $OrderLine->delivery_date }}</td>
                            <td>
                                @if(1 == $OrderLine->tasks_status )  <span class="badge badge-info">{{ __('general_content.no_task_trans_key') }}</span>@endif
                                @if(2 == $OrderLine->tasks_status )  <span class="badge badge-warning">{{ __('general_content.created_trans_key') }}</span>@endif
                                @if(3 == $OrderLine->tasks_status )  <span class="badge badge-success">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                                @if(4 == $OrderLine->tasks_status )  <span class="badge badge-danger">{{ __('general_content.finished_task_trans_key') }}</span>@endif
                            </td>
                            <td>
                                @if(1 == $OrderLine->delivery_status )  <span class="badge badge-info">{{ __('general_content.not_delivered_trans_key') }}</span>@endif
                                @if(2 == $OrderLine->delivery_status )  <span class="badge badge-warning">{{ __('general_content.partly_delivered_trans_key') }}</span>@endif
                                @if(3 == $OrderLine->delivery_status )  <span class="badge badge-success">{{ __('general_content.delivered_trans_key') }}</span>@endif
                            </td>
                            <td>
                                @if(1 == $OrderLine->invoice_status )  <span class="badge badge-info">{{ __('general_content.not_invoiced_trans_key') }}</span>@endif
                                @if(2 == $OrderLine->invoice_status )  <span class="badge badge-warning">{{ __('general_content.partly_invoiced_trans_key') }}</span>@endif
                                @if(3 == $OrderLine->invoice_status )  <span class="badge badge-success">{{ __('general_content.invoiced_trans_key') }}</span>@endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('task.manage', ['id_type'=> 'order_lines_id', 'id_page'=>  $OrderLine->orders_id, 'id_line' => $OrderLine->id])}}" class="dropdown-item" ><span class="text-success"><i class="fa fa-lg fa-fw  fas fa-list"></i> {{ __('general_content.tasks_trans_key') }}{{  $OrderLine->getTaskCountAttribute() }}</span></a></button>
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
