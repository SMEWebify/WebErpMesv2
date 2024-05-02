<x-adminlte-select name="companies_id" label="{{ __('general_content.companie_trans_key') }}" label-class="text-lightblue" igroup-size="sm">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-building"></i>
        </div>
    </x-slot>
    @foreach ($CompanieSelect as $item)
        <option value="{{ $item->id }}"  @if($item->id == $companiesId ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
    @endforeach
</x-adminlte-select>