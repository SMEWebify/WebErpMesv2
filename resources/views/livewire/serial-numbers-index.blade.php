<div>
    <div class="card">
        <div class="card-body">
            @include('include.search-card')
        </div>
        <div class="table-responsive p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{__('general_content.serial_numbers_trans_key') }}</th>
                        <th>{{ __('general_content.product_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.task_trans_key') }}</th>
                        <th>{{__('general_content.po_receipt_trans_key') }}</th>
                        <th>{{ __('general_content.statu_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($SerialNumberslist as $SerialNumbers)
                    <tr>
                        <td>{{ $SerialNumbers->serial_number }}</td>
                        <td>
                            @if($SerialNumbers->products_id ?? null)
                            <x-ButtonTextView route="{{ route('products.show', ['id' => $SerialNumbers->products_id ]) }}" /> {{ $SerialNumbers->Product['label'] }}
                            @else
                            - 
                            @endif
                        </td>
                        <td>
                            @if($SerialNumbers->order_line_id ?? null)
                            <x-OrderButton id="{{ $SerialNumbers->OrderLine->order['id'] }}" code="{{ $SerialNumbers->OrderLine->order['code'] }}"  /> {{ $SerialNumbers->OrderLine['label'] }}
                            @else
                            - 
                            @endif
                        </td>
                        <td>
                            @if($SerialNumbers->task_id ?? null)
                            <a href="{{ route('production.task.statu.id', ['id' => $SerialNumbers->task_id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                            @else
                            - 
                            @endif
                        </td>
                        
                        <td>
                            @if($SerialNumbers->purchase_receipt_line_id ?? null)
                                <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipts.show', ['id' => $SerialNumbers->purchaseReceiptLines->purchase_receipt_id])}}">
                                    <i class="fas fa-folder"></i>
                                    {{ $SerialNumbers->purchaseReceiptLines->purchaseReceipt->code }}
                                </a>
                            @else
                            - 
                            @endif
                        </td>
                        <td>
                            @if(1 == $SerialNumbers->status )<span class="badge badge-info">{{__('general_content.undefined_trans_key') }}</span>@endif
                            @if(2 == $SerialNumbers->status )<span class="badge badge-success">{{__('general_content.sold_trans_key') }}</span>@endif
                            @if(3 == $SerialNumbers->status )<span class="badge badge-secondary">{{__('general_content.shipped_trans_key') }}</span>@endif
                            @if(4 == $SerialNumbers->status )<span class="badge badge-warning">{{__('general_content.returned_trans_key') }}</span>@endif
                            @if(5 == $SerialNumbers->status )<span class="badge badge-primary">{{__('general_content.in_stock_trans_key') }}</span>@endif
                        </td>
                        <td>{{ $SerialNumbers->GetPrettyCreatedAttribute() }}</td>
                        
                    </tr>
                    @empty
                    <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                    <tfoot>
                    <tr>
                        <th>{{ __('general_content.serial_numbers_trans_key') }}</th>
                        <th>{{ __('general_content.product_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.task_trans_key') }}</th>
                        <th>{{ __('general_content.po_receipt_trans_key') }}</th>
                        <th>{{ __('general_content.statu_trans_key') }}</th>
                        <th>{{__('general_content.created_at_trans_key') }}</th>
                    </tr>
                    </tfoot>
                </tbody>
            </table>
        </div>
        <!-- /.row -->
        {{ $SerialNumberslist->links() }}
    <!-- /.card -->
    </div>
<!-- /.card-body -->
</div>