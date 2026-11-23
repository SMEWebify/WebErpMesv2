<?php

return [
    'enable' => 'Attiva prezzo calcolato',
    'disable' => 'Disattiva prezzo calcolato',
    'disable_title' => 'Il prezzo calcolato attuale sarà registrato come prezzo di vendita della riga.',
    'reason_title' => 'Ordine convalidato: il prezzo deve restare bloccato. Motivo della riattivazione del prezzo calcolato?',
    'reason_placeholder' => 'Motivo (obbligatorio)',
    'confirm' => 'Attiva',
    'cancel' => 'Annulla',

    'errors' => [
        'not_article' => 'Una riga di presentazione non ha un prezzo calcolato.',
        'shipped' => 'La riga è già stata consegnata o fatturata, anche solo in parte: il suo prezzo non può più essere ricalcolato.',
        'order_locked' => 'L\'ordine non è più in preparazione (stato oltre «Aperto» o riesame del contratto convalidato): il prezzo è bloccato.',
        'reason_required' => 'È obbligatorio un motivo per riattivare il prezzo calcolato su un ordine convalidato.',
    ],
];
