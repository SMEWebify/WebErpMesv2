<div>
    <div class="card">
        <div class="card-body">
            @if($stockAndNeed)
            <x-InfocalloutComponent note="{{ __('general_content.current_stock_note_trans_key') }}"  />
            <x-InfocalloutComponent note="{{ __('general_content.current_stock_note_2_trans_key') }}"  />
            @endif
            <div class="form-row">
                <div class="form-group col-md-2">
                    <button class="btn btn-success" wire:click="showStock">{{ __('general_content.view_stock_list_trans_key') }}</button>
                </div>
                <div class="form-group col-md-4">
                    <label for="stockAndNeed">{{ __('general_content.stock_requested_trans_key') }}</label>
                    <input type="checkbox" id="stockAndNeed" wire:model.live="stockAndNeed" style=" display:flex; align-items:center;">
                </div>
            </div>
        </div>
    </div>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ __('general_content.product_trans_key') }}</th>
                <th></th>
                <th></th>
                <th></th>
                <th>
                    @if(!$stockAndNeed)
                        {{ __('general_content.qty_trans_key') }}
                    @else
                        {{ __('general_content.qty_trans_key') }} / {{ __('general_content.requested_trans_key') }} ({{ __('general_content.order_line_trans_key') }})  ({{ __('general_content.task_trans_key') }})
                    @endif
                </th>
            </tr>
        </thead>
        <tbody>
        @foreach($produitsAvecStock as $produit)
        <tr>
            <td><x-ButtonTextView route="{{ route('products.show', ['id' => $produit->id]) }}" /></td>
            <td>{{ $produit->label }}</td>
            <td>{{ __('general_content.total_stock_trans_key') }}: {{ $produit->StockLocationProductCount() }}</td>
            <td>
                @if(!$stockAndNeed)
                    @forelse($produit->Stock_location_product as $StockLocationsProduct)
                    <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-sm btn-success">{{ $StockLocationsProduct->code}} </a><br/>
                    @empty
                    -
                    @endforelse
                @endif
            </td>

            
            <td>
                @if(!$stockAndNeed)
                    @forelse($produit->Stock_location_product as $StockLocationsProduct)
                        @if($StockLocationsProduct->getCurrentStockMove() > $StockLocationsProduct->mini_qty)
                        <li class="bg-success color-palette">
                        @elseif($StockLocationsProduct->getCurrentStockMove() < $StockLocationsProduct->mini_qty)
                        <li class="bg-danger color-palette">
                        @elseif($StockLocationsProduct->getCurrentStockMove() == $StockLocationsProduct->mini_qty)
                        <li class="bg-warning color-palette">
                        @endif
                        {{ $StockLocationsProduct->getCurrentStockMove() }}
                    </li>
                    @empty
                    -
                    @endforelse
                @else
                {{ $produit->getTotalStockMove() }} / <strong>{{ $produit->getTotalUndeliveredQtyWithoutTasksAttribute() +  $produit->getTotalUnFinishedTaskLinesQtyAttribute() }}</strong>  ({{ $produit->getTotalUndeliveredQtyWithoutTasksAttribute() }}) ({{ $produit->getTotalUnFinishedTaskLinesQtyAttribute() }})
                @endif
            </td>

            @if($stockAndNeed & ($produit->getTotalUndeliveredQtyWithoutTasksAttribute() +  $produit->getTotalUnFinishedTaskLinesQtyAttribute()) > 0)
            <td >
                <a class="btn btn-primary btn-sm" wire:click="storeOrder({{ $produit->id }}, {{ $produit->getTotalUndeliveredQtyWithoutTasksAttribute() +  $produit->getTotalUnFinishedTaskLinesQtyAttribute() }})" href="#">
                    <i class="fas fa-folder"></i>
                    {{ __('general_content.new_order_trans_key') }}
                </a>
            </td>
            @endif
        </tr>
        @endforeach
        
        </tbody>
        <tfoot>
            <tr>
                <th>{{ __('general_content.product_trans_key') }}</th>
                <th></th>
                <th></th>
                <th></th>
                <th>
                    @if(!$stockAndNeed)
                        {{ __('general_content.qty_trans_key') }}
                    @else
                        {{ __('general_content.qty_trans_key') }} / {{ __('general_content.requested_trans_key') }} ({{ __('general_content.order_line_trans_key') }})  ({{ __('general_content.task_trans_key') }})
                    @endif
                </th>
            </tr>
        </tfoot>
    </table>
</div>
