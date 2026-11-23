<?php

return [
    'errors' => [
        'reason_required' => 'Geben Sie den Grund der Stornierung an (mindestens 3 Zeichen).',
        'not_article' => 'Eine Gliederungsposition kann nicht storniert werden.',
        'already_cancelled' => 'Diese Position ist bereits storniert.',
        'fully_delivered' => 'Alles ist bereits geliefert: nichts zu stornieren. Für ein zurückgegebenes Teil erfassen Sie eine Kundenrücksendung, für einen Betrag eine Gutschrift.',
        'invoiced_beyond_delivered' => 'Diese Position ist über die gelieferte Menge hinaus fakturiert: erstellen Sie zuerst eine Gutschrift.',
        'delete_used' => 'Diese Position ist bereits geliefert oder fakturiert: sie kann nicht mehr gelöscht werden. Verwenden Sie „Position stornieren“, um den nicht gelieferten Rest zu stornieren.',
        'delivery_invoiced' => 'Diese Lieferscheinposition ist fakturiert: erstellen Sie eine Gutschrift.',
        'delivery_returned' => 'Zu dieser Lieferscheinposition gibt es eine Kundenrücksendung: sie kann nicht mehr storniert werden.',
        'delivery_packaged' => 'Diese Lieferscheinposition ist einem Packstück zugeordnet: entfernen Sie sie zuerst aus dem Packstück (Reiter Packaging).',
        'delivery_nc' => 'Eine Abweichung verweist auf diese Lieferscheinposition: sie kann nicht storniert werden.',
    ],
    'ui' => [
        'action' => 'Position stornieren',
        'title' => 'Auftragsposition stornieren',
        'message' => 'Der nicht gelieferte Rest (:qty) wird storniert: die bestellte Menge sinkt auf die gelieferte Menge (:delivered) und noch offene Arbeitsgänge werden geschlossen. Die Position bleibt sichtbar, gekennzeichnet als „Storniert“.',
        'reason' => 'Grund',
        'reason_help' => 'Zum Beispiel: Position vom Kunden storniert, E-Mail vom 16.11.',
        'confirm' => 'Position stornieren',
        'close' => 'Schließen',
        'badge' => 'Storniert',
        'badge_title' => 'Storniert am :at — :qty storniert — :reason',
        'done' => 'Position storniert',
        'delivery_action' => 'Diese Lieferscheinposition stornieren',
        'delivery_title' => 'Lieferscheinposition stornieren',
        'delivery_message' => 'Die Position verschwindet vom Lieferschein und ihre Menge geht in die Restliefermenge des Auftrags zurück. Hatte der Lieferschein das Teil aus dem Lager ausgebucht, wird es wieder eingebucht.',
        'delivery_done' => 'Lieferscheinposition storniert',
        'delivery_stock_restored' => ':qty wieder eingelagert.',
    ],
];
