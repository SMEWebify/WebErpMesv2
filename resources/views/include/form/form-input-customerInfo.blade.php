<x-adminlte-input name="customer_reference" label="{{ __('general_content.customer_reference_trans_key') }}" placeholder="{{ __('general_content.customer_reference_trans_key') }}" value="{{  $customerReference }}" label-class="text-lightblue">
    <x-slot name="prependSlot">
        <div class="input-group-text">
            <i class="fas fa-user-tag  text-info"></i>
        </div>
    </x-slot>
</x-adminlte-input>