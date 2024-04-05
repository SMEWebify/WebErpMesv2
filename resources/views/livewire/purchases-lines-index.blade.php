<div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
        </div>
        <div class="table-responsive p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{ __('general_content.product_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                        <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                        <th>{{ __('general_content.discount_trans_key') }}</th>
                        <th>{{ __('general_content.total_selling_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($PurchasesLineslist as $PurchaseLine)
                    <tr>
                        <td>
                            @if($PurchaseLine->tasks->OrderLines ?? null)
                                <x-OrderButton id="{{ $PurchaseLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseLine->tasks->OrderLines->order->code }}"  /> 
                            @else
                            {{__('general_content.generic_trans_key') }} 
                            @endif
                        </td>
                        <td>@if($PurchaseLine->tasks->OrderLines ?? null){{ $PurchaseLine->tasks->OrderLines->qty }} x @endif</td>
                        <td>@if($PurchaseLine->tasks->OrderLines ?? null){{ $PurchaseLine->tasks->OrderLines->label }}@endif</td>
                        <td>
                            <a href="{{ route('production.task.statu.id', ['id' => $PurchaseLine->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                            #{{ $PurchaseLine->tasks->id }} - {{ $PurchaseLine->tasks->label }}
                            @if($PurchaseLine->tasks->component_id )
                                - {{ $PurchaseLine->tasks->Component['label'] }}
                            @endif
                        </td>
                        
                        <td>
                            @if($PurchaseLine->tasks->component_id ) 
                            <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseLine->tasks->component_id])}}" />
                            @endif
                        </td>
                        <td>{{ $PurchaseLine->qty }}</td>
                        <td>{{ $PurchaseLine->receipt_qty }}</td>
                        <td>{{ $PurchaseLine->invoiced_qty }}</td>
                        <td>{{ $PurchaseLine->selling_price }} {{ $Factory->curency }}</td>
                        <td>{{ $PurchaseLine->discount }} %</td>
                        <td>{{ $PurchaseLine->total_selling_price }} {{ $Factory->curency }}</td>
                    </tr>
                    @empty
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                    <tfoot>
                    <tr>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                        <th>{{__('general_content.label_trans_key') }}</th>
                        <th>{{ __('general_content.product_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                        <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                        <th>{{ __('general_content.discount_trans_key') }}</th>
                        <th>{{ __('general_content.total_selling_trans_key') }}</th>
                    </tr>
                    </tfoot>
                </tbody>
            </table>
        </div>
        <!-- /.row -->
        {{ $PurchasesLineslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>