<x-adminlte-select name="accounting_payment_conditions_id" label="{{ __('general_content.payment_conditions_trans_key') }}" label-class="text-lightblue" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-secondary">
            <i class="fas fa-cash-register"></i>
        </div>
    </x-slot>
    @foreach ($AccountingConditionSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $accountingPaymentConditionsId ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
    @endforeach
</x-adminlte-select>