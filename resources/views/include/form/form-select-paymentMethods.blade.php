<x-adminlte-select name="accounting_payment_methods_id" label="{{ __('general_content.select_payement_methods_trans_key') }}" label-class="text-sky" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-secondary bg-gradient">
            <i class="fas fa-comment-dollar"></i>
        </div>
    </x-slot>
    @foreach ($AccountingMethodsSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $accountingPaymentMethodsId ) Selected @endif >{{ $item->label }}</option>
    @endforeach
</x-adminlte-select>