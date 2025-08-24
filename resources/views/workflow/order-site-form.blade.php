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
                    <label for="location">{{ __('general_content.location_trans_key') }}</label>
                    <input type="text" class="form-control" name="location" value="{{ old('location', $OrderSite->location ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="characteristics">{{ __('general_content.characteristics_trans_key') }}</label>
                    <textarea class="form-control" name="characteristics">{{ old('characteristics', $OrderSite->characteristics ?? '') }}</textarea>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="contact_info">{{ __('general_content.contact_info_trans_key') }}</label>
                    <textarea class="form-control" name="contact_info">{{ old('contact_info', $OrderSite->contact_info ?? '') }}</textarea>
                </div>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
            </x-slot>
        </x-adminlte-card>
    </form>
@endif

