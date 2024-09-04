
<x-adminlte-select2 name="companies_addresses_id" id="companies_addresses_id" label="{{ __('general_content.adress_name_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.adress_name_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-map-marked-alt"></i>
        </div>
    </x-slot>
    @foreach ($AddressSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $adressId ) Selected @endif >{{ $item->label }} - {{ $item->adress }}</option>
    @endforeach
</x-adminlte-select2>