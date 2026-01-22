<div class="tab-pane" id="PurchaseQuotationLines" wire:ignore.self>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @include('include.alert-result')
                    <input type="hidden" wire:model.live="purchase_quotation_id">

                    @if($OrderStatu == 1)
                        @if($updateLines)
                        <form wire:submit.prevent="updatePurchaseQuotationLine">
                            <input type="hidden" wire:model.live="purchase_quotation_line_id">
                        @else
                        <form wire:submit.prevent="storePurchaseQuotationLine">
                        @endif
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="ordre">{{ __('general_content.sort_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                        </div>
                                        <input type="number" class="form-control @error('ordre') is-invalid @enderror" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" min="0" wire:model.live="ordre">
                                    </div>
                                    @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <x-adminlte-select name="product_id" id="product_id" label="{{ __('general_content.product_trans_key') }}" label-class="text-lightblue"
                                        igroup-size="s" data-placeholder="{{ __('general_content.select_product_trans_key') }}" wire:model.live="product_id" wire:change.prevent="ChangeCodelabel()">
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text bg-gradient-info">
                                                <i class="fas fa-barcode"></i>
                                            </div>
                                        </x-slot>
                                        <option value="">{{ __('general_content.select_product_trans_key') }}</option>
                                        @foreach ($ProductsSelect as $item)
                                        <option value="{{ $item->id }}" data-txt="{{ $item->code }}">{{ $item->code }} - {{ $item->label }}</option>
                                        @endforeach
                                    </x-adminlte-select>
                                    @error('product_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <input type="text" class="code form-control @error('code') is-invalid @enderror" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" wire:model.live="code">
                                    </div>
                                    @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="line_label">{{ __('general_content.description_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control @error('line_label') is-invalid @enderror" id="line_label" placeholder="{{ __('general_content.description_trans_key') }}" wire:model.live="line_label">
                                    </div>
                                    @error('line_label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="qty_to_order">{{ __('general_content.qty_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-times"></i></span>
                                        </div>
                                        <input type="number" class="form-control @error('qty_to_order') is-invalid @enderror" id="qty_to_order" placeholder="{{ __('general_content.qty_trans_key') }}" min="0" wire:model.live="qty_to_order">
                                    </div>
                                    @error('qty_to_order') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="unit_price">{{ __('general_content.price_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $Factory->curency ?? 'EUR' }}</span>
                                        </div>
                                        <input type="number" class="form-control @error('unit_price') is-invalid @enderror" id="unit_price" placeholder="{{ __('general_content.price_trans_key') }}" min="0" step="0.001" wire:model.live="unit_price">
                                    </div>
                                    @error('unit_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success btn-block">{{ __('general_content.submit_trans_key') }}</button>
                                </div>
                            </div>
                        </form>
                    @else
                    <x-adminlte-alert theme="info" title="Info">
                        {{ __('general_content.info_statu_trans_key') }}
                    </x-adminlte-alert>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 table-responsive">
            <form method="POST" action="{{ route('purchases.orders.store', ['id' => $PurchaseQuotation->id])}}" >
                @csrf
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{__('general_content.total_price_trans_key') }}</th>
                            <th>{{ __('general_content.action_trans_key') }}</th>
                            <th>{{__('general_content.qty_accepted_trans_key') }}</th>
                            <th>{{ __('general_content.qty_canceled_trans_key') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($PurchaseQuotation->PurchaseQuotationLines as $PurchaseQuotationLine)
                        <tr>
                            <td>
                                @if($PurchaseQuotationLine->tasks?->OrderLines)
                                    <x-OrderButton id="{{ $PurchaseQuotationLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseQuotationLine->tasks->OrderLines->order->code }}"  />
                                @else
                                {{__('general_content.generic_trans_key') }}
                                @endif
                            </td>
                            <td>
                                @if($PurchaseQuotationLine->tasks?->OrderLines)
                                    {{ $PurchaseQuotationLine->tasks->OrderLines->qty }} x {{ $PurchaseQuotationLine->tasks->qty }}
                                @else
                                    {{__('general_content.generic_trans_key') }}
                                @endif
                            </td>
                            <td>
                                @if($PurchaseQuotationLine->tasks?->OrderLines)
                                    {{ $PurchaseQuotationLine->tasks->OrderLines->label }}
                                @else
                                    {{ $PurchaseQuotationLine->label }}
                                @endif
                            </td>
                            <td>
                                @if($PurchaseQuotationLine->tasks)
                                <a href="{{ route('production.task.statu.id', ['id' => $PurchaseQuotationLine->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                #{{ $PurchaseQuotationLine->tasks->id }} - {{ $PurchaseQuotationLine->tasks->label }}
                                @if($PurchaseQuotationLine->tasks->component_id )
                                    - {{ $PurchaseQuotationLine->tasks->Component['label'] }}
                                @endif
                                @else
                                {{ $PurchaseQuotationLine->label }}
                                @endif
                            </td>
                            <td>
                                @if($PurchaseQuotationLine->tasks?->component_id )
                                <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseQuotationLine->tasks->component_id])}}" />
                                @elseif($PurchaseQuotationLine->product_id)
                                <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseQuotationLine->product_id])}}" />
                                @endif
                            </td>
                            <td>{{ number_format($PurchaseQuotationLine->qty_to_order, 0, '', ' ') }}</td>
                            <td>{{ $PurchaseQuotationLine->formatted_unit_price }}</td>
                            <td>{{ $PurchaseQuotationLine->formatted_total_price }}</td>
                            <td>
                                @if($PurchaseQuotation->statu == 1)
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2" wire:click.prevent="editPurchaseQuotationLine({{ $PurchaseQuotationLine->id }})">
                                    <i class="fas fa-edit"></i> {{ __('general_content.edit_trans_key') }}
                                </button>
                                @endif
                                @if($PurchaseQuotationLine->qty_to_order > $PurchaseQuotationLine->qty_accepted)
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="hidden" value="{{ $PurchaseQuotationLine->tasks->id ?? 0 }}" name="PurchaseQuotationLineTaskid[]" >
                                        <input class="custom-control-input" value="{{ $PurchaseQuotationLine->id }}" name="PurchaseQuotationLine[]" id="PurchaseQuotationLine.{{ $PurchaseQuotationLine->id }}" type="checkbox">
                                        <label for="PurchaseQuotationLine.{{ $PurchaseQuotationLine->id }}" class="custom-control-label">+</label>
                                    </div>
                                    <label for="purchase_price_{{ $PurchaseQuotationLine->id }}">{{ __('general_content.proposed_purchase_price_trans_key') }}</label>
                                    <input type="number" class="form-control" name="PurchaseQuotationLinePrice[]" id="purchase_price_{{ $PurchaseQuotationLine->id }}" step="0.001" value="{{ $PurchaseQuotationLine->unit_price?? 0 }}">
                                </div>
                                @endif
                            </td>
                            <td>{{ number_format($PurchaseQuotationLine->qty_accepted, 0, '', ' ') }}</td>
                            <td>{{ number_format($PurchaseQuotationLine->canceled_qty, 0, '', ' ') }}</td>
                        </tr>
                        @empty
                        <x-EmptyDataLine col="11" text="{{ __('general_content.no_data_trans_key') }}"  />
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>{{ __('general_content.order_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                            <th>{{__('general_content.label_trans_key') }}</th>
                            <th>{{ __('general_content.product_trans_key') }}</th>
                            <th>{{ __('general_content.qty_trans_key') }}</th>
                            <th>{{ __('general_content.price_trans_key') }}</th>
                            <th>{{__('general_content.total_price_trans_key') }}</th>
                            <th>
                                @if($PurchaseQuotation->statu != 6)
                                <button type="Submit" class="btn btn-primary">{{ __('general_content.new_order_trans_key') }}</button>
                                @endif
                            </th>
                            <th>{{__('general_content.qty_accepted_trans_key') }}</th>
                            <th>{{ __('general_content.qty_canceled_trans_key') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
        <!-- /.col-12 table-responsive-->
    </div>
    <!-- /.row -->
</div>
