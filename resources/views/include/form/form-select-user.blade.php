
<x-adminlte-select2 name="user_id" id="user_id" label="{{ __('general_content.user_trans_key') }}" label-class="text-info"
    igroup-size="s" data-placeholder="{{ __('general_content.user_trans_key') }}">
    <x-slot name="prependSlot">
        <div class="input-group-text bg-gradient-info">
            <i class="fas fa-user"></i>
        </div>
    </x-slot>
    <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
    @foreach ($userSelect as $item)
    <option value="{{ $item->id }}" @if($item->id == $userId ) Selected @endif>{{ $item->name }}</option>
    @endforeach
</x-adminlte-select2>