
<x-adminlte-select2 name="quality_non_conformitie_id" id="quality_non_conformitie_id" label="{{ __('general_content.non_conformitie_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.non_conformitie_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-exclamation"></i>
        </div>
    </x-slot>
    <option value="NULL">-</option>
    @foreach ($NonConformitysSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $ncId ) Selected @endif>{{ $item->code }}</option>
    @endforeach
</x-adminlte-select2>