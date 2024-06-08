
<div>
    <div class="card">
        <div class="card-body">
            @include('include.alert-result')

            @if($OrderStatu == 1)
                @if($updateLines)
                <form wire:submit.prevent="update">
                            <input type="hidden" wire:model.live="order_lines_id">
                            @include('livewire.form.line-update')
                @else
                <form wire:submit.prevent="storeOrderLine">
                            <input type="hidden"  name="orders_id"  id="orders_id" value="1" wire:model.live="orders_id" >
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
                                <td>
                                    @if($PurchaseLine->tasks->OrderLines ?? null)
                                        {{ $PurchaseLine->tasks->OrderLines->qty }} x 
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
                                <td>{{ $PurchaseLine->qty }}</td>
                                <td>{{ $PurchaseLine->receipt_qty }}</td>
                                <td>{{ $PurchaseLine->invoiced_qty }}</td>
                                <td>{{ $PurchaseLine->selling_price }} {{ $Factory->curency }}</td>
                                <td>{{ $PurchaseLine->discount }} %</td>
                                <td>{{ $PurchaseLine->total_selling_price }} {{ $Factory->curency }}</td>
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
                                <th>{{ __('general_content.total_selling_trans_key') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>