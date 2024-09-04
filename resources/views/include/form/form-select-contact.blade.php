<x-adminlte-select2 name="companies_contacts_id" id="companies_contacts_id" label="{{ __('general_content.contact_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.contact_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-user"></i>
        </div>
    </x-slot>
    @foreach ($ContactSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $contactId ) Selected @endif>{{ $item->first_name }} - {{ $item->name }}</option>
    @endforeach
</x-adminlte-select2>