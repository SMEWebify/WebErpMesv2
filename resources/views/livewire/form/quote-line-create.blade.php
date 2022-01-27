<form wire:submit.prevent="storeQuoteLine">
    <div class="row">
        <div class="col-2">
            <input type="hidden"  name="quotes_id"  id="quotes_id" value="1" wire:model="quotes_id" >
            <label for="ordre">Sort order:</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                </div>
                <input type="number" class="form-control @error('ordre') is-invalid @enderror" id="ordre" placeholder="Enter order" wire:model="ordre">
            </div>
            @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="code">External ID</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                </div>
                <input type="text" class="code form-control @error('code') is-invalid @enderror" id="code" placeholder="Enter external ID" wire:model="code">
            </div>
            @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <label for="product_id">Product</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                </div>
                <select class="product_id form-control @error('product_id') is-invalid @enderror"  name="product_id" id="product_id"  wire:model="product_id">
                    <option value="" >Select Product</option>
                    @foreach ($ProductsSelect as $item)
                    <option value="{{ $item->id }}" data-txt="{{ $item->code }}" >{{ $item->code }} - {{ $item->label }}</option>
                    @endforeach
                </select>
            </div>
            
            @error('product_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="label">Description :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" placeholder="Description" wire:model="label">
                @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
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
                <select class="form-control @error('methods_units_id') is-invalid @enderror" name="methods_units_id" id="methods_units_id"  wire:model="methods_units_id">
                    <option value="" >Select Unit</option>
                    @foreach ($UnitsSelect as $item)
                    <option value="{{ $item->id }}" >{{ $item->code }} - {{ $item->label }}</option>
                    @endforeach
                </select>
                @error('methods_units_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
            </div>
        </div>
        <div class="col-2">
            <label for="selling_price">Selling price :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">{{ $Factory->curency }}</span>
                </div>
                <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" placeholder="Selling price" wire:model="selling_price" step=".001" value="0">
            </div>
            @error('selling_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="discount">Discount :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount" placeholder="Discount" wire:model="discount" step=".01" value="0">
            </div>
            @error('discount') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <label for="accounting_vats_id">VAT type</label>
            <select class="form-control @error('accounting_vats_id') is-invalid @enderror" name="accounting_vats_id" id="accounting_vats_id"  wire:model="accounting_vats_id">
                <option value="" >Select VAT</option>
                @foreach ($VATSelect as $item)
                    <option value="{{ $item->id }}" >{{ $item->label }}</option>
                @endforeach
            </select>
            @error('accounting_vats_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
            <label for="delivery_date">Delevery date</label>
            <input type="date" class="form-control" @error('delivery_date') is-invalid @enderror name="delivery_date"  id="delivery_date" wire:model="delivery_date">
            @error('delivery_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="col-2">
            <br/>
            <button type="submit" class="btn btn-success btn-block">Add</button>
        </div>
    </div>
</form>