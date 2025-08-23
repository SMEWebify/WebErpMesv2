@if(isset($Order))
    <form action="{{ $OrderSite ? route('orders.site.update', ['order' => $Order->id, 'site' => $OrderSite->id]) : route('orders.site.store', ['id' => $Order->id]) }}" method="POST">
        @csrf
        @if($OrderSite)
            @method('PUT')
        @endif
        <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="name">{{ __('general_content.name_trans_key') }}</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $OrderSite->name ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                    <label for="adress">{{ __('general_content.adress_trans_key') }}</label>
                    <input type="text" class="form-control" name="adress" value="{{ old('adress', $OrderSite->adress ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="city">{{ __('general_content.city_trans_key') }}</label>
                    <input type="text" class="form-control" name="city" value="{{ old('city', $OrderSite->city ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                    <label for="postal_code">{{ __('general_content.postal_code_trans_key') }}</label>
                    <input type="text" class="form-control" name="postal_code" value="{{ old('postal_code', $OrderSite->postal_code ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="description">{{ __('general_content.description_trans_key') }}</label>
                    <textarea class="form-control" name="description">{{ old('description', $OrderSite->description ?? '') }}</textarea>
                </div>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
            </x-slot>
        </x-adminlte-card>
    </form>
@endif

