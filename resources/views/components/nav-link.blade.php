@props(['active'])

<a {{ $attributes->merge(['class' => 'nav-link' . ($active ?? false ? ' active' : '')]) }}>
    {{ $slot }}
</a>
