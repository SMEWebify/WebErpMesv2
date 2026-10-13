<x-adminlte-select name="methods_locations_id" label="{{ __('general_content.processing_location_trans_key') }}" label-class="text-sky" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-secondary bg-gradient">
            <i class="fas fa-map-marker-alt"></i>
        </div>
    </x-slot>
    <option value="">{{ __('general_content.select_location_trans_key') }}</option>
    @foreach ($MethodsLocationsSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $methodsLocationId) Selected @endif>{{ $item->label }}</option>
    @endforeach
</x-adminlte-select>