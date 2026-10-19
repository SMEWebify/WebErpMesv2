
<x-adminlte-select-bs :config="['allowEmptyOption' => true]" name="companies_id" id="companies_id" label="{{ __('general_content.companie_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.companie_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-info bg-gradient">
            <i class="fas fa-building"></i>
        </div>
    </x-slot>
    <option value="">{{ __('general_content.select_company_trans_key') }}</option>
    @foreach ($CompanieSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $companiesId ) Selected @endif>{{ $item->label }}</option>
    @endforeach
</x-adminlte-select-bs>

{{-- Changer de société recharge les adresses et contacts (devis, commande, achat, lead, opportunité). --}}
@push('js')
@include('include.form.company-address-contact-sync', [
    'url' => route('invoices.company-ac'),
    'companyId' => 'companies_id',
    'addressId' => 'companies_addresses_id',
    'contactId' => 'companies_contacts_id',
])
@endpush
