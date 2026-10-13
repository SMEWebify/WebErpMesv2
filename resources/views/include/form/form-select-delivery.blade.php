<x-adminlte-select name="accounting_deliveries_id" label="{{ __('general_content.delevery_method_trans_key') }}" label-class="text-sky" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-secondary bg-gradient">
            <i class="fas fa-truck"></i>
        </div>
    </x-slot>
    @foreach ($AccountingDeleveriesSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $accountingDeliveriesId ) Selected @endif >{{ $item->label }}</option>
    @endforeach
</x-adminlte-select>