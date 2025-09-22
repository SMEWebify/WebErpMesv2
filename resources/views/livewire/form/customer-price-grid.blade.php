<div class="mt-3">
    <div class="card card-outline card-info">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ __('general_content.customer_price_grid_trans_key') }}</h3>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="usePriceListSwitch-{{ $priceListToggleKey }}" wire:model.live="usePriceList" @if(!$product_id) disabled @endif>
                <label class="custom-control-label" for="usePriceListSwitch-{{ $priceListToggleKey }}">{{ __('general_content.automatic_pricing_trans_key') }}</label>
            </div>
        </div>
        <div class="card-body">
            @if($priceSource)
                <p class="mb-2"><strong>{{ __('general_content.price_source_trans_key') }}:</strong> {{ $priceSource }}</p>
            @endif
            @if(!$product_id)
                <p class="text-muted mb-0">{{ __('general_content.select_product_trans_key') }}</p>
            @elseif(empty($customerPriceList))
                <p class="text-muted mb-0">{{ __('general_content.no_price_defined_trans_key') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('general_content.source_trans_key') }}</th>
                                <th>{{ __('general_content.quantite_min_trans_key') }}</th>
                                <th>{{ __('general_content.quantite_max_trans_key') }}</th>
                                <th>{{ __('general_content.price_trans_key') }}</th>
                                <th class="text-right">{{ __('general_content.action_trans_key') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customerPriceList as $entry)
                                @php
                                    $rowClass = '';
                                    if (!empty($entry['selected'])) {
                                        $rowClass = 'table-success';
                                    } elseif (!empty($entry['matches'])) {
                                        $rowClass = 'table-warning';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>{{ $entry['scope_label'] }}</td>
                                    <td>{{ $entry['min_qty'] }}</td>
                                    <td>
                                        @if(is_null($entry['max_qty']))
                                            &infin;
                                        @else
                                            {{ $entry['max_qty'] }}
                                        @endif
                                    </td>
                                    <td>{{ $entry['formatted_price'] }}</td>
                                    <td class="text-right">
                                        <button type="button" class="btn btn-xs btn-outline-primary" wire:click="applyPriceFromList({{ $entry['id'] }})" @if(!$usePriceList && $appliedPriceListId === $entry['id']) disabled @endif>
                                            {{ __('general_content.use_price_trans_key') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
