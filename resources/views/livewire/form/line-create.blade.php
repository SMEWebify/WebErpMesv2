    <div class="form-row">
        <div class="form-group col-md-2">
            <label for="ordre">{{ __('general_content.sort_trans_key') }} :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                </div>
                <input type="number" class="form-control @error('ordre') is-invalid @enderror" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" min="0" wire:model.live="ordre" >
            </div>
            @error('ordre') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="product_id">{{ __('general_content.product_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                </div>
                <select wire:click.prevent="ChangeCodelabel()" class="product_id form-control @error('product_id') is-invalid @enderror"  name="product_id" id="product_id"  wire:model.live="product_id">
                    <option value="" >{{ __('general_content.select_product_trans_key') }}</option>
                    @foreach ($ProductsSelect as $item)
                    <option value="{{ $item->id }}" data-txt="{{ $item->code }}" >{{ $item->code }} - {{ $item->label }}</option>
                    @endforeach
                </select>
            </div>
            @error('product_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="qty">{{ __('general_content.qty_trans_key') }} :</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-times"></i></span>
                </div>
                <input type="number" class="form-control @error('qty') is-invalid @enderror" id="qty" placeholder="{{ __('general_content.qty_trans_key') }}" min="0" wire:model.live="qty">
            </div>
            @error('qty') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="selling_price">{{ __('general_content.price_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">{{ $Factory->curency }}</span>
                </div>
                <input type="number" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" placeholder="{{ __('general_content.price_trans_key') }}" min="0" wire:model.live="selling_price" step=".001" value="0">
            </div>
            @error('selling_price') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="accounting_vats_id">{{ __('general_content.vat_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <select class="form-control @error('accounting_vats_id') is-invalid @enderror" name="accounting_vats_id" id="accounting_vats_id"  wire:model.live="accounting_vats_id">
                    <option value="" >{{ __('general_content.select_vat_trans_key') }}</option>
                    @foreach ($VATSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->label }}</option>
                    @endforeach
                </select>
            </div>
            @error('accounting_vats_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
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
            <label for="label">{{ __('general_content.description_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" placeholder="{{ __('general_content.description_trans_key') }}" wire:model.live="label">
            </div>
            @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="methods_units_id">{{ __('general_content.unit_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                </div>
                <select class="form-control @error('methods_units_id') is-invalid @enderror" name="methods_units_id" id="methods_units_id"  wire:model.live="methods_units_id">
                    <option value="" >{{ __('general_content.select_unit_trans_key') }}</option>
                    @foreach ($UnitsSelect as $item)
                    <option value="{{ $item->id }}" >{{ $item->code }} - {{ $item->label }}</option>
                    @endforeach
                </select>
            </div>
            @error('methods_units_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="discount">{{ __('general_content.discount_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <input type="number" class="form-control @error('discount') is-invalid @enderror" id="discount" placeholder="{{ __('general_content.discount_trans_key') }}" wire:model.live="discount" step=".01" value="0">
            </div>
            @error('discount') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <label for="delivery_date">{{ __('general_content.delivery_date_trans_key') }}</label>
            <input type="date" class="form-control" @error('delivery_date') is-invalid @enderror name="delivery_date"  id="delivery_date" wire:model.live="delivery_date">
            @error('delivery_date') <span class="text-danger">{{ $message }}<br/></span>@enderror
        </div>
        <div class="form-group col-md-2">
            <br/>
            <button type="submit" class="btn btn-success btn-block">{{ __('general_content.submit_trans_key') }}</button>
        </div>
    </div>
</form>