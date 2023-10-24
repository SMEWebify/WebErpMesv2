<x-adminlte-select name="companies_addresses_id" label="{{ __('general_content.adress_name_trans_key') }}" label-class="text-lightblue" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-map-marked-alt"></i>
        </div>
    </x-slot>
    @foreach ($AddressSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $adressId ) Selected @endif >{{ $item->label }} - {{ $item->adress }}</option>
    @endforeach
</x-adminlte-select>