<?php

// Textes des briques React partagées (DataTable, Pagination, dépôt CAO), posés par le gabarit dans <meta name="ui-common"> (lib/i18n.js, commonText).
return [
    'show_column' => 'Mostra di nuovo la colonna',
    'hide_column' => 'Nascondi la colonna',
    'date_from' => 'Dal',
    'date_to' => 'Al',
    'results' => 'risultati',
    'result' => 'risultato',
    'cad_no_file' => 'Nessun file riconosciuto (:formats).',
    'cad_import_error' => 'Errore durante l\'importazione.',
    'cad_network_error' => 'Errore di rete durante l\'invio.',
    'cad_importing' => 'Importazione in corso…',
    'cad_drop' => 'Trascina i file',
    'cad_help' => 'Il disegno caricato viene collegato alla riga nella gestione documentale (tranne .sym, .drg e .bvc, letti interamente all\'importazione). Un .geo produce inoltre un SVG utilizzabile dal nesting. Un nesting RADAN (.drg) crea una riga per pezzo, quantità = pezzi per lamiera × numero di lamiere. Un job BySoft (.bvc) crea una riga per pezzo con il relativo contorno in SVG, quantità = quantità del job. Un disegno .pdf viene letto dall\'IA (Integrazioni → IA): verificare il cartiglio sulla riga creata.',
    'cad_drop_help' => 'qui per creare le righe automaticamente  — oppure fai clic per selezionare',
];
