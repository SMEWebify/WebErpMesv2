{{-- Type d'équipement (App\Enums\EquipmentKind) : $id, $value, $help (texte sous le champ). --}}
<div class="mb-3">
  <label for="{{ $id }}">{{ __('equipment.kind') }}</label>
  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-industry"></i></span>
    <select class="form-select" name="equipment_kind" id="{{ $id }}">
      <option value="">{{ __('equipment.kind_none') }}</option>
      @foreach (\App\Enums\EquipmentKind::options() as $kindValue => $kindLabel)
        <option value="{{ $kindValue }}" @selected(($value ?? null) === $kindValue)>{{ $kindLabel }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-text">{{ $help }}</div>
</div>
