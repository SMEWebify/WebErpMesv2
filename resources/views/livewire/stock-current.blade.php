<div>
    <div class="card">
        <div class="card-body">
            <x-InfocalloutComponent note="{{ __('general_content.current_stock_note_trans_key') }}"  />
            <x-InfocalloutComponent note="{{ __('general_content.current_stock_note_2_trans_key') }}"  />
            <div class="form-row">
                <div class="form-group col-md-2">
                    <button class="btn btn-success" wire:click="showStock">{{ __('general_content.view_stock_list_trans_key') }}</button>
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
            </tr>
        </thead>
        <tbody>
        @foreach($produitsAvecStock as $produit)
            @php 
                $TotalStockMove = $produit->getTotalStockMove();
                $QtyNeed = $produit->getTotalUndeliveredQtyWithoutTasksAttribute() +  $produit->getTotalUnFinishedTaskLinesQtyAttribute();
            @endphp
        <tr>
            <td><x-ButtonTextView route="{{ route('products.show', ['id' => $produit->id]) }}" /></td>
            <td>{{ $produit->label }}</td>
            @if($TotalStockMove > $QtyNeed)
            <td class="bg-success color-palette">
            @elseif($TotalStockMove < $QtyNeed)
            <td class="bg-danger color-palette">
            @elseif($TotalStockMove == $QtyNeed)
            <td class="bg-warning color-palette">
            @endif
                {{ __('general_content.qty_trans_key') }} / {{ __('general_content.requested_trans_key') }} ({{ __('general_content.order_line_trans_key') }})  ({{ __('general_content.task_trans_key') }})<br/>
                {{ $TotalStockMove }} / <strong>{{ $QtyNeed }}</strong>  ({{ $produit->getTotalUndeliveredQtyWithoutTasksAttribute() }}) ({{ $produit->getTotalUnFinishedTaskLinesQtyAttribute() }})<br/>
                @if( $QtyNeed > 0)
                    <a class="btn btn-primary btn-sm" wire:click="storeOrder({{ $produit->id }}, {{ $produit->getTotalUndeliveredQtyWithoutTasksAttribute() +  $produit->getTotalUnFinishedTaskLinesQtyAttribute() }})" href="#">
                        <i class="fas fa-folder"></i>
                        {{ __('general_content.new_order_trans_key') }}
                    </a>
                @endif
            </td>
            <td>
                {{ __('general_content.total_stock_trans_key') }}: {{ $produit->StockLocationProductCount() }}<br/>
                @forelse($produit->Stock_location_product as $StockLocationsProduct)
                    @if($StockLocationsProduct->getCurrentStockMove() > $StockLocationsProduct->mini_qty)
                    <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-sm btn-success">{{ $StockLocationsProduct->code}} </a> {{ __('general_content.qty_trans_key') }} : {{ $StockLocationsProduct->getCurrentStockMove() }}<br/>
                    @elseif($StockLocationsProduct->getCurrentStockMove() < $StockLocationsProduct->mini_qty)
                    <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-sm btn-danger">{{ $StockLocationsProduct->code}} </a> {{ __('general_content.qty_trans_key') }} : {{ $StockLocationsProduct->getCurrentStockMove() }}<br/>
                    @elseif($StockLocationsProduct->getCurrentStockMove() == $StockLocationsProduct->mini_qty)
                    <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-sm btn-warning">{{ $StockLocationsProduct->code}} </a> {{ __('general_content.qty_trans_key') }} :  {{ $StockLocationsProduct->getCurrentStockMove() }}<br/>
                    @endif
                @empty
                -
                @endforelse
            </td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>{{ __('general_content.product_trans_key') }}</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>
