
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            @if($OrderStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="updatePurchaseLine">
                            <input type="hidden" wire:model.lazy="purchase_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeOrderLine">
                            <input type="hidden"  name="purchase_id"  id="purchase_id" value="1" wire:model.lazy="purchase_id" >
                            @include('livewire.form.line-create')
                @endif
            @else
            <x-adminlte-alert theme="info" title="Info">
                {{ __('general_content.info_statu_trans_key') }}
            </x-adminlte-alert>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    @include('include.search-card')
                </div>
            </div>
            <div class="table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                            <tr>
                                <th>{{ __('general_content.order_trans_key') }}</th>
                                <th>{{ __('general_content.qty_trans_key') }}</th>
                                <th>{{ __('general_content.order_trans_key') }} {{ __('general_content.label_trans_key') }}</th>
                                <th>{{__('general_content.label_trans_key') }}</th>
                                <th>{{ __('general_content.product_trans_key') }}</th>
                                <th>{{ __('general_content.qty_trans_key') }}</th>
                                <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                                <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                                <th>{{ __('general_content.price_trans_key') }}</th>
                                <th>{{ __('general_content.discount_trans_key') }}</th>
                                <th>{{ __('general_content.vat_trans_key') }}</th> 
                                <th>{{__('general_content.action_trans_key') }}</th>
                                <th></th>
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
                                <td>
                                    @if($PurchaseLine->tasks->OrderLines ?? null)
                                        {{ $PurchaseLine->tasks->OrderLines->qty }} x {{ $PurchaseLine->tasks->qty }}
                                    @else
                                        {{__('general_content.generic_trans_key') }} 
                                    @endif
                                </td>
                                <td>
                                    @if($PurchaseLine->tasks->OrderLines ?? null)
                                        {{ $PurchaseLine->tasks->OrderLines->label }}
                                    @else
                                        {{__('general_content.generic_trans_key') }} 
                                    @endif
                                </td>
                                <td>
                                    @if($PurchaseLine->tasks_id ?? null)
                                        <a href="{{ route('production.task.statu.id', ['id' => $PurchaseLine->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                        #{{ $PurchaseLine->tasks->id }} - {{ $PurchaseLine->tasks->label }}
                                        @if($PurchaseLine->tasks->component_id )
                                            - {{ $PurchaseLine->tasks->Component['label'] }}
                                        @endif
                                    @else
                                        {{ $PurchaseLine->label }}
                                    @endif
                                </td>
                                
                                <td>
                                    
                                    @if($PurchaseLine->tasks_id ?? null)
                                        @if($PurchaseLine->tasks->component_id ) 
                                        <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseLine->tasks->component_id])}}" />
                                        @endif
                                    @else
                                        @if($PurchaseLine->product_id ) 
                                            <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseLine->product_id])}}" />
                                        @endif
                                    @endif
                                </td>
                                <td>{{ number_format($PurchaseLine->qty, 0, '', ' ') }}</td>
                                <td>
                                    @if($PurchaseLine->receipt_qty > 0)
                                    <a href="#" data-toggle="modal" data-target="#modalReceiptFor{{ $PurchaseLine->id }}"><span class="badge badge-success">{{ number_format($PurchaseLine->receipt_qty, 0, '', ' ') }}</span></a>
                                    {{-- Modal for purchase order detail --}}
                                    <x-adminlte-modal id="modalReceiptFor{{ $PurchaseLine->id }}" title="{{__('general_content.po_receipt_trans_key') }}" theme="info"
                                        icon="fas fa-bolt" size='lg' disable-animations>
                                        <ul>
                                            @foreach($PurchaseLine->purchaseReceiptLines as $purchaseReceiptLine)
                                                <li>
                                                    {{ __('general_content.delivery_notes_trans_key') }}: {{ $purchaseReceiptLine->purchaseReceipt->code }} <br>
                                                    {{ __('general_content.qty_trans_key') }} : {{ $purchaseReceiptLine->receipt_qty }} <br>
                                                    {{__('general_content.created_at_trans_key') }} : {{ $purchaseReceiptLine->GetPrettyCreatedAttribute() }} <br>
                                                    <x-ButtonTextView route="{{ route('purchase.receipts.show', ['id' => $purchaseReceiptLine->purchase_receipt_id])}}" />
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-adminlte-modal>
                                    @else
                                    <span class="badge badge-primary" >{{ number_format($PurchaseLine->receipt_qty, 0, '', ' ') }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format($PurchaseLine->invoiced_qty, 0, '', ' ') }}</td>
                                <td>{{ $PurchaseLine->formatted_selling_price }}</td>
                                <td>{{ $PurchaseLine->discount }} %</td>
                                <td> 
                                    @if($PurchaseLine->accounting_vats_id)
                                    {{ $PurchaseLine->VAT['rate'] }} %
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    @if($OrderStatu == 1)
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <div class="dropdown-menu">
                                        <a href="#" class="dropdown-item" wire:click="editPurchaseLine({{$PurchaseLine->id}})"><span class="text-warning"><i class="fa fa-lg fa-fw  fa-edit"></i> {{ __('general_content.edit_line_trans_key') }}</span></a>
                                    </div>
                                    @endif
                                </td>
                                
                                <td>
                                    @if($PurchaseLine->qty > $PurchaseLine->receipt_qty)
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" value="{{ $PurchaseLine->id }}" wire:model.lazy="data.{{ $PurchaseLine->id }}.purchase_line_id" id="data.{{ $PurchaseLine->id }}.purchase_line_id"  type="checkbox">
                                        <label for="data.{{ $PurchaseLine->id }}.purchase_line_id" class="custom-control-label">+</label>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <x-EmptyDataLine col="11" text="{{ __('general_content.no_data_trans_key') }}"  />
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
                                <th>{{ __('general_content.vat_trans_key') }}</th>
                                <th>{{__('general_content.action_trans_key') }}</th>
                                <th >
                                    @if($OrderStatu == 1)
                                    <a class="btn btn-primary btn-sm" wire:click="storeReciep({{ $purchase_id }})" href="#">
                                        <i class="fas fa-folder"></i>
                                        {{ __('general_content.new_receipt_document_trans_key') }}
                                    </a>
                                    @endif
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>