<div>
    
    <div class="card-body" >
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search fa-fw"></i></span>
            </div>
            <input type="text" class="form-control" wire:model.live="search" placeholder="{{ __('general_content.search_task_trans_key') }}">
        </div>
    </div>

    @empty($StockDetails)
    <h1>No Stock</h1> 
    @else
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Stock - {{ $StockDetails->StockLocationProducts->product->code }}
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="{{ __('general_content.collapse_trans_key') }}">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove" title="{{ __('general_content.remove_trans_key') }}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('general_content.user_trans_key') }}</th>
                        <th>{{__('general_content.date_time_trans_key') }}</th>
                        <th>{{ __('general_content.qty_trans_key') }}</th>
                        <th>{{ __('general_content.order_trans_key') }}</th>
                        <th>{{ __('general_content.task_trans_key') }}</th>
                        <th>{{__('general_content.po_receipt_trans_key') }}</th>
                        <th>{{ __('general_content.type_trans_key') }}</th>
                        <th>{{ __('general_content.price_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $StockDetails->UserManagement['name'] }}</td>
                        <td>{{ $StockDetails->GetPrettyCreatedAttribute() }}</td>
                        <td>{{ $StockDetails->qty }}</td>
                        <td>
                            @if($StockDetails->order_line_id)
                            <x-OrderButton id="{{ $StockDetails->OrderLine->order['id'] }}" code="{{ $StockDetails->OrderLine->order['code'] }}"  />
                            @endif
                            </td>
                            <td>
                            @if($StockDetails->task_id)
                            <a href="{{ route('production.task.statu.id', ['id' => $StockDetails->task_id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                            @endif
                            </td>
                            <td>
                            @if($StockDetails->purchase_receipt_line_id)
                            <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipts.show', ['id' => $StockDetails->purchaseReceiptLines->purchase_receipt_id])}}">
                                <i class="fas fa-folder"></i>
                                {{ $StockDetails->purchaseReceiptLines->purchaseReceipt->code }}
                            </a>
                            @endif
                        </td>
                        <td>
                            @if(1 == $StockDetails->typ_move ){{__('general_content.inventories_trans_key') }} @endif
                            @if(2 == $StockDetails->typ_move ){{__('general_content.task_allocation_trans_key') }} @endif
                            @if(3 == $StockDetails->typ_move ){{__('general_content.purchase_order_reception_trans_key') }} @endif
                            @if(4 == $StockDetails->typ_move ){{__('general_content.inter_stock_mvts_trans_key') }} @endif
                            @if(5 == $StockDetails->typ_move ){{__('general_content.manual_stock_recep_trans_key') }} @endif
                            @if(6 == $StockDetails->typ_move ){{__('general_content.manual_stock_dispatching_trans_key') }} @endif
                            @if(7 == $StockDetails->typ_move ){{__('general_content.reservation_trans_key') }} @endif
                            @if(8 == $StockDetails->typ_move ){{__('general_content.reservation_cancellation_trans_key') }} @endif
                            @if(9 == $StockDetails->typ_move ){{__('general_content.part_delivery_trans_key') }} @endif
                            @if(10 == $StockDetails->typ_move ){{__('general_content.in_production_trans_key') }} @endif
                            @if(11 == $StockDetails->typ_move ){{__('general_content.reservation_component_production_trans_key') }} @endif
                            @if(12 == $StockDetails->typ_move ){{__('general_content.manufactured_component_entry_trans_key') }} @endif
                            @if(13 == $StockDetails->typ_move ){{__('general_content.direct_inventory_trans_key') }} @endif
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <p>
                BARECODE
            </p>
            @php echo '<img src="data:image/jpeg;base64,' . DNS1D::getBarcodePNG  (strval($StockDetails->id), $Factory->task_barre_code,4,60,array(1,1,1), true) . '" alt="barcode"   />'; @endphp
        </div>
    </div>
    @endempty
</div>
