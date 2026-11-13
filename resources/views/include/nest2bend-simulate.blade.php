@php
    /**
     * Bouton « Simuler le pliage » (NEST2BEND) d'une ligne de devis.
     *
     * Requis : $line (QuoteLines), $idQuote, $bending (BendingSimulationService::buttonState()).
     * Rien n'est affiché sans liaison NEST2BEND vérifiée (écran commercial). Aucune donnée
     * secrète ici : le navigateur ne connaît que les routes de l'ERP.
     */
    $n2bTrans = collect(['label', 'starting', 'saving', 'no_step', 'step_file', 'close', 'modal_title', 'remarks', 'renew', 'restart', 'restart_hint'])
        ->mapWithKeys(fn ($key) => [$key => __('nest2bend.button.' . $key)])
        ->merge([
            'expired'     => __('nest2bend.state.expired'),
            'unavailable' => __('nest2bend.errors.unavailable'),
            'unexpected'  => __('nest2bend.errors.unexpected'),
        ])
        ->merge(collect(['stored', 'pending', 'retry', 'retrying', 'open'])->mapWithKeys(fn ($key) => ['sheet_' . $key => __('nest2bend.sheet.' . $key)]))
        ->merge(collect(['title', 'press', 'tools', 'sequence', 'sheet'])->mapWithKeys(fn ($key) => ['last_' . $key => __('nest2bend.last.' . $key)]));
@endphp

@if($bending['visible'])
    <div data-react="bending-simulation"
         data-session-url="{{ route('quotes.lines.nest2bend.session', ['quoteId' => $idQuote, 'id' => $line->id]) }}"
         data-result-url="{{ route('quotes.lines.nest2bend.result', ['quoteId' => $idQuote, 'id' => $line->id]) }}"
         data-sheet-url="{{ route('quotes.lines.nest2bend.sheet', ['quoteId' => $idQuote, 'id' => $line->id]) }}"
         data-last="{{ json_encode($bending['last'] ?? null) }}"
         data-active="{{ $bending['expired'] ? '0' : '1' }}"
         data-expired="{{ $bending['expired'] ? '1' : '0' }}"
         data-has-step="{{ $bending['step'] ? '1' : '0' }}"
         data-step-name="{{ $bending['step'] }}"
         data-warning="{{ $bending['warning'] }}"
         data-app-url="{{ $bending['app_url'] }}"
         data-fileable-type="quote-line"
         data-fileable-id="{{ $line->id }}"
         data-trans="{{ json_encode($n2bTrans) }}"
         class="mb-3"></div>
@endif
