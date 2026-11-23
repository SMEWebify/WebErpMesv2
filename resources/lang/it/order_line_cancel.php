<?php

return [
    'errors' => [
        'reason_required' => 'Indicate il motivo dell\'annullamento (almeno 3 caratteri).',
        'not_article' => 'Una riga di presentazione non si annulla.',
        'already_cancelled' => 'Questa riga è già annullata.',
        'fully_delivered' => 'È già tutto consegnato: nulla da annullare. Per un pezzo restituito, fate un reso cliente; per un importo, una nota di credito.',
        'invoiced_beyond_delivered' => 'Questa riga è fatturata oltre quanto consegnato: fate prima una nota di credito.',
        'delete_used' => 'Questa riga è già consegnata o fatturata: non può più essere eliminata. Usate « Annulla la riga » per annullare il residuo non consegnato.',
        'delivery_invoiced' => 'Questa riga di DDT è fatturata: fate una nota di credito.',
        'delivery_returned' => 'Questa riga di DDT ha un reso cliente: non può più essere annullata.',
        'delivery_packaged' => 'Questa riga di DDT è inserita in un collo: toglietela prima dal collo (scheda Packaging).',
        'delivery_nc' => 'Una non conformità cita questa riga di DDT: non può essere annullata.',
    ],
    'ui' => [
        'action' => 'Annulla la riga',
        'title' => 'Annulla la riga d\'ordine',
        'message' => 'Il residuo non consegnato (:qty) viene annullato: la quantità ordinata scende alla quantità consegnata (:delivered) e le fasi ancora aperte vengono chiuse. La riga resta visibile, contrassegnata « Annullata ».',
        'reason' => 'Motivo',
        'reason_help' => 'Per esempio: riga annullata dal cliente, mail del 16/11.',
        'confirm' => 'Annulla la riga',
        'close' => 'Chiudi',
        'badge' => 'Annullata',
        'badge_title' => 'Annullata il :at — :qty annullata/e — :reason',
        'done' => 'Riga annullata',
        'delivery_action' => 'Annulla questa riga di DDT',
        'delivery_title' => 'Annulla la riga di DDT',
        'delivery_message' => 'La riga scompare dal DDT e la sua quantità torna nel residuo da consegnare dell\'ordine. Se il DDT aveva scaricato il pezzo dal magazzino, viene ricaricato.',
        'delivery_done' => 'Riga di DDT annullata',
        'delivery_stock_restored' => ':qty ricaricata/e a magazzino.',
    ],
];
