<?php

return [
    'short' => 'RP',
    'label' => 'Rilascio per la produzione',
    'release' => 'Rilascio per la produzione',
    'revoke' => 'Annulla il rilascio',
    'released' => 'RP',
    'not_released' => 'Senza RP',
    'released_on' => 'RP il :date da :user',
    'revoke_confirm' => 'Annullare il rilascio per la produzione di questa riga?',
    'bulk_action' => 'Rilascio per la produzione',
    'bulk_result' => ':count riga/righe rilasciata/e per la produzione.',
    'bulk_refused' => 'Non rilasciata/e:',
    'bulk_none' => 'Le righe selezionate hanno già il rilascio per la produzione.',
    'released_ok' => 'Rilascio per la produzione dato.',
    'revoked_ok' => 'Rilascio per la produzione annullato.',

    // Refus
    'blocked_start' => 'La riga d\'ordine :line non ha il rilascio per la produzione dell\'ufficio tecnico: la fase non può essere avviata.',
    'blocked_kanban' => 'Fasi senza rilascio per la produzione, spostamento annullato: :tasks.',
    'not_article' => 'Una riga di presentazione non si produce.',
    'already_released' => 'Questa riga ha già il rilascio per la produzione.',
    'not_released_yet' => 'Questa riga non ha il rilascio per la produzione.',
    'review_rejected' => 'Il riesame dell\'ordine è respinto: nessun rilascio per la produzione.',
    'change_pending' => 'Una richiesta di modifica è in attesa di approvazione sull\'ordine: nessun rilascio per la produzione prima della sua approvazione.',
    'amendment_pending' => 'Una variante contrattuale attende il visto del riesame del contratto: nessun rilascio per la produzione prima di questo visto.',
    'already_started' => 'Una fase di questa riga è già stata avviata: il rilascio per la produzione non può più essere annullato.',

    // Écrans
    'planning_unreleased' => ':count senza RP',
    'gantt_unreleased' => '(senza RP)',
];
