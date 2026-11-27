{{--
    « Lire à voix haute » (synthèse vocale du navigateur, lib/readAloud.js) : seulement pour
    l'utilisateur qui a choisi la « Lecture facilitée » (Profil → Apparence). Masqué tant que le
    script n'a pas vérifié que le navigateur sait lire.
    Paramètre : target = id du champ ou de l'éditeur riche ; sans lui, premier textarea du bloc
    [data-read-aloud-scope].
--}}
@if(\App\Support\UiTheme::readable(auth()->user()))
<span class="d-none" data-read-aloud @isset($target) data-target="{{ $target }}" @endisset>
    <button type="button" class="btn btn-sm btn-outline-secondary" aria-pressed="false" data-read-aloud-button
            data-read="{{ __('read_aloud.read') }}" data-stop="{{ __('read_aloud.stop') }}">
        <i class="fas fa-volume-up me-1"></i><span data-label>{{ __('read_aloud.read') }}</span>
    </button>
</span>
@endif
