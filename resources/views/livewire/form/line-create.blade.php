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