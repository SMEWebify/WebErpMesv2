@props(['value'])
<label {{ $attributes->merge(['class' => 'form-label fw-medium']) }}>
    {{ $value ?? $slot }}
</label>
