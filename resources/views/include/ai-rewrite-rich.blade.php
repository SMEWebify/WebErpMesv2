{{--
    Outils sous un éditeur riche (<x-adminlte-text-editor>) :
    - « Réécrire sans fautes » : texte corrigé morceau par morceau, la mise en forme ne bouge pas
      (TextRewriter::rewriteHtml) ;
    - « Lire à voix haute » pour qui a choisi la lecture facilitée (include/read-aloud-button).
    Paramètres : target = id de l'éditeur (son « name » par défaut) ; personal = le contenu
    cite des tiers (e-mail, piste, opportunité) : réécriture proposée seulement si l'entreprise
    autorise l'envoi de données personnelles au provider (carte Intégrations → IA).
--}}
@php
    $rewriteRichShown = app(\App\Services\AI\TextRewriter::class)->isAvailable()
        && (! ($personal ?? true) || app(\App\Services\AI\AISettingsResolver::class)->allowsPersonalData());
    $readAloudShown = \App\Support\UiTheme::readable(auth()->user());
@endphp
@if($rewriteRichShown || $readAloudShown)
<div class="mb-3" @if($rewriteRichShown) data-ai-rewrite-rich data-target="{{ $target }}" data-url="{{ route('ai.rewrite') }}" data-trans="{{ json_encode(__('ai_text')) }}" @endif>
    <div class="d-flex flex-wrap gap-1">
        @if($rewriteRichShown)
        <button type="button" class="btn btn-sm btn-outline-secondary" data-ai-rewrite-rich-button>
            <i class="fas fa-spell-check me-1"></i>{{ __('ai_text.rewrite') }}
        </button>
        @endif
        @include('include.read-aloud-button', ['target' => $target])
    </div>
    @if($rewriteRichShown)
    <div data-ai-rewrite-result></div>
    @endif
</div>
@endif
