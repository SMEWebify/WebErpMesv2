<form wire:submit.prevent="update">
    <div class="row">
        <div class="col-1">
            <label for="ORDRE">Sort order:</label>
            <input type="hidden" wire:model="quote_lines_id">
            <input type="number" class="form-control @error('ORDRE') is-invalid @enderror" id="ORDRE" placeholder="Enter order" wire:model="ORDRE">
            @error('ORDRE') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <label for="CODE">External ID:</label>
            <input type="text" class="form-control @error('CODE') is-invalid @enderror" id="CODE" placeholder="Enter external ID" wire:model="CODE">
            @error('CODE') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <label for="product_id">Product</label>
            <select class="form-control" name="product_id" id="product_id"  wire:model="product_id" >
                @foreach ($ProductsSelect as $item)
                <option value="{{ $item->id }}" data-txt="{{ $item->CODE }}" >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-1">
            <label for="LABEL">Description :</label>
            <input type="text" class="form-control @error('LABEL') is-invalid @enderror" id="LABEL" placeholder="Description" wire:model="LABEL">
            @error('LABEL') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <label for="qty">Quantity :</label>
            <input type="number" class="form-control @error('qty') is-invalid @enderror" id="qty" placeholder="Quantity" wire:model="qty">
            @error('qty') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <label for="methods_units_id">Unit</label>
            <select class="form-control" name="methods_units_id" id="methods_units_id"  wire:model="methods_units_id">
                @foreach ($UnitsSelect as $item)
                <option value="{{ $item->id }}" >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                 @endforeach
            </select>
        </div>
        <div class="col-1">
            <label for="selling_price">Selling price :</label>
            <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" placeholder="Selling price" wire:model="selling_price" step=".001">
            @error('selling_price') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <label for="discount">Discount :</label>
            <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount" placeholder="Discount" wire:model="discount" step=".01">
            @error('discount') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <label for="accounting_vats_id">VAT type</label>
            <select class="form-control" name="accounting_vats_id" id="accounting_vats_id"  wire:model="accounting_vats_id">
                @foreach ($VATSelect as $item)
                <option value="{{ $item->id }}" >{{ $item->LABEL }}</option>
                 @endforeach
            </select>
        </div>

        <div class="col-1">
            <label for="delivery_date">Delevery date</label>
            <input type="date" class="form-control" @error('delivery_date') is-invalid @enderror name="delivery_date"  id="delivery_date" wire:model="delivery_date">
            @error('delivery_date') <span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="col-1">
            <br/>
             <button type="submit" class="btn btn-success btn-block">Update</button>
        </div>
    </div>
</form>