
<x-adminlte-select-bs :config="['allowEmptyOption' => true]" name="methods_services_id" id="methods_services_id" label="{{ __('general_content.service_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.service_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-info bg-gradient">
            <i class="fas fa-list"></i>
        </div>
    </x-slot>
    <option value="">{{ __('general_content.no_service_trans_key') }}</option>
    @foreach ($ServicesSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $serviceId ) Selected @endif>{{ $item->label }}</option>
    @endforeach
</x-adminlte-select-bs>