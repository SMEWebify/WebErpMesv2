@props([
    'h1',
    'badge'    => null,
    'previous' => null,
    'list'     => null,
    'next'     => null,
    'steps'    => null,
    'statu'    => null,
    'endpoint' => null,
    'redirect' => null,
])

@once
@push('css')
<style>
.doc-header {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    column-gap: 1rem;
    row-gap: .5rem;
    margin-bottom: .5rem;
}
.doc-header__title {
    font-size: 1.4rem;
    font-weight: 500;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.doc-header__steps { min-width: 0; }
.doc-header__nav { margin-left: auto; white-space: nowrap; }
.doc-header__nav .btn { padding: .3rem .7rem; }
.doc-header__nav .btn.disabled { opacity: .4; pointer-events: none; }

/* Variante compacte des flèches de workflow (ArrowSteps.jsx) une fois dans le header.
   La spécificité à 2 classes prime sur les règles .as-btn injectées par le composant React. */
.doc-header__steps .as-track { padding: 0; }
.doc-header__steps .as-btn {
    padding: 7px 16px;
    font-size: 11.5px;
    min-width: 62px;
}
.doc-header__steps .as-btn:first-child { padding-left: 10px; }
.doc-header__steps .as-btn:last-child  { padding-right: 10px; }
.doc-header__steps .as-btn.as-current  { font-size: 12px; }
.doc-header__steps .alert { font-size: 12px; padding: .3rem .6rem; margin-bottom: .35rem; }

/* Sous 1200px les flèches reprennent leur propre ligne, titre et boutons restent appairés */
@media (max-width: 1199.98px) {
    .doc-header { grid-template-columns: minmax(0, 1fr) auto; }
    .doc-header__steps { grid-column: 1 / -1; grid-row: 2; }
}
@media (max-width: 575.98px) {
    .doc-header__title { white-space: normal; font-size: 1.2rem; }
}
</style>
@endpush
@endonce

<div class="doc-header">
    <h1 class="doc-header__title">
        @if($badge)<span class="badge badge-warning mr-2">{{ $badge }}</span>@endif
        {{ $h1 }}
    </h1>

    @if($steps)
        <div class="doc-header__steps"
             data-react="arrow-steps"
             data-steps="{{ is_string($steps) ? $steps : json_encode($steps) }}"
             data-statu="{{ $statu }}"
             data-endpoint="{{ $endpoint }}"
             data-redirect="{{ $redirect }}"></div>
    @else
        <div class="doc-header__steps"></div>
    @endif

    {{-- Les chevrons ne s'affichent que si la page gère la navigation entre documents.
         Ni l'un ni l'autre renseigné = pas de navigation (ex. proformas) : on ne montre que le retour liste. --}}
    @php $hasNavigation = $previous || $next; @endphp
    <div class="doc-header__nav btn-group btn-group-sm" role="group">
        @if($hasNavigation)
        <a href="{{ $previous ?: '#' }}"
           class="btn btn-outline-secondary {{ $previous ? '' : 'disabled' }}"
           title="{{ __('general_content.previous_trans_key') }}"
           aria-label="{{ __('general_content.previous_trans_key') }}"
           @if(!$previous) tabindex="-1" aria-disabled="true" @endif>
            <i class="fas fa-chevron-left"></i>
        </a>
        @endif
        @if($list)
            <a href="{{ $list }}"
               class="btn btn-outline-secondary"
               title="{{ __('general_content.back_to_list_trans_key') }}"
               aria-label="{{ __('general_content.back_to_list_trans_key') }}">
                <i class="fas fa-list"></i>
            </a>
        @endif
        @if($hasNavigation)
        <a href="{{ $next ?: '#' }}"
           class="btn btn-outline-secondary {{ $next ? '' : 'disabled' }}"
           title="{{ __('general_content.next_trans_key') }}"
           aria-label="{{ __('general_content.next_trans_key') }}"
           @if(!$next) tabindex="-1" aria-disabled="true" @endif>
            <i class="fas fa-chevron-right"></i>
        </a>
        @endif
    </div>
</div>
