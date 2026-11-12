{{-- Rattachement d'un achat à une affaire hors tâche (PurchaseAffairLink) : valeur « type:id » --}}
<x-adminlte-select-bs :config="['allowEmptyOption' => true]" name="affair_link" id="affair_link"
    label="{{ __('purchase_affair.link') }}" label-class="text-secondary" igroup-size="s">
    <x-slot name="prependSlot">
        <div class="input-group-text text-bg-secondary bg-gradient">
            <i class="fas fa-link"></i>
        </div>
    </x-slot>
    <option value="">{{ __('purchase_affair.none') }}</option>
    @foreach ($affairLink['options'] as $group)
        <optgroup label="{{ $group['group'] }}">
            @foreach ($group['options'] as $option)
                <option value="{{ $option['value'] }}" @selected($option['value'] === $affairLink['value'])>{{ $option['label'] }}</option>
            @endforeach
        </optgroup>
    @endforeach
</x-adminlte-select-bs>
<small class="form-text text-muted">
    {{ __('purchase_affair.help') }}
    @if ($affairLink['current'])
        — <a href="{{ $affairLink['current']['url'] }}">{{ $affairLink['current']['label'] }}</a>
    @endif
</small>
