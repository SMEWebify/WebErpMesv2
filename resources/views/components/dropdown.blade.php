@props(['align' => 'right', 'width' => ''])

<div class="dropdown" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            class="dropdown-menu{{ $align === 'left' ? '' : ' dropdown-menu-end' }} show"
            style="display: none;"
            @click="open = false">
        {{ $content }}
    </div>
</div>
