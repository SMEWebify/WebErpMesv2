<x-adminlte-select name="accounting_deliveries_id" label="{{ __('general_content.delevery_method_trans_key') }}" label-class="text-lightblue" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-secondary">
            <i class="fas fa-truck"></i>
        </div>
    </x-slot>
    @foreach ($AccountingDeleveriesSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $accountingDeliveriesId ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
    @endforeach
</x-adminlte-select>