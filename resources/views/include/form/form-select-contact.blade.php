<x-adminlte-select name="companies_contacts_id" label="Contact" label-class="text-lightblue" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-user"></i>
        </div>
    </x-slot>
    @foreach ($ContactSelect as $item)
        <option value="{{ $item->id }}" @if($item->id == $contactId ) Selected @endif >{{ $item->first_name }} - {{ $item->name }}</option>
    @endforeach
</x-adminlte-select>