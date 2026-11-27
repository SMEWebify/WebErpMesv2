{{--
    « Réécrire sans fautes » sous un éditeur riche (<x-adminlte-text-editor>) : le texte est
    corrigé morceau par morceau, la mise en forme ne bouge pas (TextRewriter::rewriteHtml).
    Paramètres : target = id de l'éditeur (son « name » par défaut) ; personal = le contenu
    cite des tiers (e-mail, piste, opportunité) : bouton posé seulement si l'entreprise autorise
    l'envoi de données personnelles au provider (carte Intégrations → IA).
--}}
@php
    $rewriteRichShown = app(\App\Services\AI\TextRewriter::class)->isAvailable()
        && (! ($personal ?? true) || app(\App\Services\AI\AISettingsResolver::class)->allowsPersonalData());
@endphp
@if($rewriteRichShown)
<div class="mb-3" data-ai-rewrite-rich data-target="{{ $target }}" data-url="{{ route('ai.rewrite') }}" data-trans="{{ json_encode(__('ai_text')) }}">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-ai-rewrite-rich-button>
        <i class="fas fa-spell-check me-1"></i>{{ __('ai_text.rewrite') }}
    </button>
    <div data-ai-rewrite-result></div>
</div>
@endif
