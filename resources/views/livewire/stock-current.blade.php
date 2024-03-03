<div>
    <div class="card">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-2">
                    <button class="btn btn-success" wire:click="showStock">Afficher la liste des stocks</button>
                </div>
                <div class="form-group col-md-4">
                    <label for="showProductsWithStock">Afficher les produits avec stock</label>
                    <input type="checkbox" id="showProductsWithStock" wire:model.live="showProductsWithStock" style=" display:flex; align-items:center;">
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
                <th>{{ __('general_content.qty_trans_key') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($produitsAvecStock as $produit)
        <tr>
            <td><x-ButtonTextView route="{{ route('products.show', ['id' => $produit->id]) }}" /></td>
            <td>{{ $produit->label }}</td>
            <td>Stock total: {{ $produit->StockLocationProductCount() }}</td>
            <td>
                @if($showProductsWithStock)
                    @foreach($produit->Stock_location_product as $StockLocationsProduct)
                    <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-sm btn-success">{{ $StockLocationsProduct->code}} </a><br/>
                    @endforeach
                @endif
            </td>

            <td>
                @if($showProductsWithStock)
                    @foreach($produit->Stock_location_product as $StockLocationsProduct)
                        @if($StockLocationsProduct->getCurrentStockMove() > $StockLocationsProduct->mini_qty)
                        <li class="bg-success color-palette">
                        @elseif($StockLocationsProduct->getCurrentStockMove() < $StockLocationsProduct->mini_qty)
                        <li class="bg-danger color-palette">
                        @elseif($StockLocationsProduct->getCurrentStockMove() == $StockLocationsProduct->mini_qty)
                        <li class="bg-warning color-palette">
                        @endif
                        {{ $StockLocationsProduct->getCurrentStockMove() }}
                    </li>
                    @endforeach
                @else
                {{ $produit->getTotalStockMove() }} / {{ $produit->getTotalUndeliveredQtyAttribute() }} 
                @endif
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
                <th>{{ __('general_content.qty_trans_key') }}</th>
            </tr>
        </tfoot>
    </table>
</div>
