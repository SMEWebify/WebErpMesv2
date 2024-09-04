
<x-adminlte-select2 name="companies_id" id="companies_id" label="{{ __('general_content.companie_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.companie_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-building"></i>
        </div>
    </x-slot>
    <option value="">{{ __('general_content.select_company_trans_key') }}</option>
    @foreach ($CompanieSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $companiesId ) Selected @endif>{{ $item->code }} - {{ $item->label }}</option>
    @endforeach
</x-adminlte-select2>