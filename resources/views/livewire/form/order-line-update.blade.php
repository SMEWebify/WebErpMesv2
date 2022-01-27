<form wire:submit.prevent="update">
    <div class="row">
        <div class="col-2">
            <label for="ordre">Sort order:</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                </div>
                <input type="hidden" wire:model="order_lines_id">
                <input type="number" class="form-control @error('ordre') is-invalid @enderror" id="ordre" placeholder="Enter order" wire:model="ordre">
            </div>
            @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="code">External ID</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" placeholder="Enter external ID" wire:model="code">
            </div>
            @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <label for="product_id">Product</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                </div>
                <select class="form-control" name="product_id" id="product_id"  wire:model="product_id" >
                    @foreach ($ProductsSelect as $item)
                    <option value="{{ $item->id }}" data-txt="{{ $item->code }}" >{{ $item->code }} - {{ $item->label }}</option>
                    @endforeach
                </select>
            </div>
            <label for="label">Description :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" placeholder="Description" wire:model="label">
            </div>
            @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <label for="qty">Quantity :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-times"></i></span>
                </div>
                <input type="number" class="form-control @error('qty') is-invalid @enderror" id="qty" placeholder="Quantity" wire:model="qty">
            </div>
            @error('qty') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="methods_units_id">Unit</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                </div>
                <select class="form-control" name="methods_units_id" id="methods_units_id"  wire:model="methods_units_id">
                    @foreach ($UnitsSelect as $item)
                    <option value="{{ $item->id }}" >{{ $item->code }} - {{ $item->label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-2">
            <label for="selling_price">Selling price :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">{{ $Factory->curency }}</span>
                </div>
                <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" placeholder="Selling price" wire:model="selling_price" step=".001">
            </div>
            @error('selling_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="discount">Discount :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount" placeholder="Discount" wire:model="discount" step=".01">
            </div>
            @error('discount') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <label for="accounting_vats_id">VAT type</label>
            <select class="form-control" name="accounting_vats_id" id="accounting_vats_id"  wire:model="accounting_vats_id">
                @foreach ($VATSelect as $item)
                    <option value="{{ $item->id }}" >{{ $item->label }}</option>
                @endforeach
            </select>
            <label for="delivery_date">Delevery date</label>
            <input type="date" class="form-control" @error('delivery_date') is-invalid @enderror name="delivery_date"  id="delivery_date" wire:model="delivery_date">
            @error('delivery_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <br/>
            <button type="submit" class="btn btn-success btn-block">Update</button>
            <button onclick="location.reload();"  class="btn btn-primary btn-block">Refresh Page</button>
        </div>
    </div>
</form>