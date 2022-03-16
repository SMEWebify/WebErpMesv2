<x-adminlte-select name="accounting_payment_methods_id" label="Payment methods" label-class="text-lightblue" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-secondary">
            <i class="fas fa-comment-dollar"></i>
        </div>
    </x-slot>
    @foreach ($AccountingConditionSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $accountingPaymentMethodsId ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
    @endforeach
</x-adminlte-select>