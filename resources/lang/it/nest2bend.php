<?php

return [
    // Écran de configuration
    'title'          => 'NEST2BEND',
    'subtitle'       => 'simulazione di piegatura su pressa piegatrice',
    'config'         => 'Collegamento con NEST2BEND',
    'base_url'       => 'URL di NEST2BEND',
    'base_url_hint'  => 'Ad esempio https://app.nest2bend.fr',
    'api_key'        => 'Chiave API',
    'api_key_hint'   => 'Chiave n2b_… creata in NEST2BEND. Qui viene cifrata e non viene più visualizzata.',
    'api_key_keep'   => 'lasciare vuoto per conservarla',
    'clear_key'      => 'Elimina la chiave (ritorno alla schermata commerciale)',
    'save'           => 'Salva',
    'saved'          => 'Collegamento NEST2BEND salvato.',
    'test_button'    => 'Verifica la connessione',
    'test_running'   => 'Verifica in corso…',
    'test_ok'        => 'Connessione riuscita: :societe.',
    'test_network_error' => 'La verifica non è andata a buon fine:',
    'open_app'       => 'Apri NEST2BEND',
    'configure'      => 'Configura',
    'card_lead'      => 'Simulazione di piegatura in iframe dalle righe di preventivo che hanno un file STEP.',
    'origin_title'   => 'Origine da dichiarare in NEST2BEND',
    'origin_intro'   => 'NEST2BEND accetta di essere visualizzato solo nelle origini che conosce. Dichiarate questa (schema, host e porta, senza percorso):',
    'what_title'     => 'Come funziona',
    'what_1'         => 'Su una riga di preventivo la cui gestione documentale contiene un file STEP, il pulsante « Simula » apre NEST2BEND nell\'ERP.',
    'what_2'         => 'Il file parte dal server dell\'ERP; la chiave API non lascia mai il server.',
    'what_3'         => 'L\'utente verifica il pezzo e la simulazione in NEST2BEND, senza effettuare l\'accesso.',
    'what_4'         => 'Il tempo considerato (attrezzaggio e tempo unitario) viene aggiunto al commento interno della riga.',
    'checked_at'     => 'Ultimo controllo',
    'never_checked'  => 'mai',
    'societe'        => 'Azienda',
    'etat'           => 'Abbonamento',
    'echeance'       => 'Scadenza',
    'modules'        => 'Moduli',
    'presses_max'    => 'Presse',
    'last_error'     => 'Ultimo incidente',

    'badge' => [
        'none'    => 'Non configurato',
        'unknown' => 'Da verificare',
        'invalid' => 'Chiave rifiutata',
        'expired' => 'Abbonamento scaduto',
        'active'  => 'Attivo',
    ],

    'state' => [
        'none'    => 'Nessuna chiave NEST2BEND registrata.',
        'unknown' => 'La chiave non è ancora stata verificata: verificate la connessione.',
        'invalid' => 'Chiave non valida o revocata.',
        'expired' => 'Abbonamento NEST2BEND scaduto.',
        'warning' => 'Abbonamento NEST2BEND :etat fino al :echeance.',
    ],

    'etats' => [
        'actif'         => 'attivo',
        'essai'         => 'in periodo di prova',
        'grace'         => 'in periodo di tolleranza',
        'lecture_seule' => 'in sola lettura',
        ''              => '—',
    ],

    // Bouton « Simuler » (ligne de devis)
    'button' => [
        'label'      => 'Simula la piegatura',
        'starting'   => 'Apertura di NEST2BEND…',
        'saving'     => 'Salvataggio del tempo…',
        'no_step'    => 'Aggiungete il file STEP del pezzo nei documenti della riga.',
        'step_file'  => 'File inviato: :name',
        'close'      => 'Chiudi',
        'modal_title'=> 'NEST2BEND — simulazione di piegatura',
        'remarks'    => 'Osservazioni di NEST2BEND',
        'renew'      => 'Rinnova l\'abbonamento',
        'restart'    => 'Riavvia',
        'restart_hint' => 'Pagina non trovata o simulazione persa? Apre una nuova sessione NEST2BEND.',
    ],

    'result_saved' => 'Tempo di piegatura aggiunto al commento interno: :summary',

    // Fiche de réglage PDF (champ `fiche` du résultat), rangée dans la GED de la ligne
    'sheet' => [
        'comment'           => 'Scheda di attrezzaggio NEST2BEND (pressa piegatrice)',
        'stored'            => 'Scheda di attrezzaggio archiviata nei documenti della riga: :name',
        'pending'           => 'Recupero della scheda di attrezzaggio in corso…',
        'retry'             => 'Recupera la scheda',
        'retrying'          => 'Recupero della scheda…',
        'open'              => 'Apri la scheda',
        'none'              => 'Nessuna scheda di attrezzaggio da recuperare per questa riga.',
        'error_404'         => 'Scheda di attrezzaggio non disponibile: pezzo non simulato, nessun punzone scelto, oppure un utensile o la pressa sono stati rimossi dall\'officina NEST2BEND dopo la simulazione.',
        'error_key'         => 'Scheda di attrezzaggio non recuperata: chiave API NEST2BEND rifiutata o abbonamento scaduto.',
        'error_unavailable' => 'Scheda di attrezzaggio non recuperata: NEST2BEND non disponibile. Riprovate più tardi (il risultato resta leggibile per 30 giorni).',
        'error_other'       => 'Scheda di attrezzaggio non recuperata (stato :status).',
    ],

    // Dernière simulation de la ligne (resultat.simulation)
    'last' => [
        'title'    => 'Ultima simulazione',
        'press'    => 'Pressa',
        'tools'    => 'Utensili',
        'sequence' => 'Sequenza di piegatura',
        'sheet'    => 'Scheda di attrezzaggio',
        'punch'    => 'Punzone :tool',
        'die'      => 'matrice :tool',
        'flipped'  => '(capovolto)',
    ],

    'comment' => [
        'entry'       => '[NEST2BEND :date] :summary',
        'preparation' => 'Attrezzaggio :time',
        'unit'        => ':time/pezzo (lotto :qty)',
        'thickness'   => 'sp. :t mm',
        'no_time'     => 'nessun tempo considerato',
    ],

    'errors' => [
        'unauthorized'    => 'Chiave API NEST2BEND non valida o revocata.',
        'inactive'        => 'Abbonamento NEST2BEND scaduto.',
        'not_found'       => 'Risultato NEST2BEND non trovato per questa riga.',
        'invalid'         => 'NEST2BEND ha rifiutato la richiesta.',
        'unavailable'     => 'NEST2BEND momentaneamente non disponibile, riprovate.',
        'rate_limited'    => 'Troppe richieste verso NEST2BEND, riprovate tra un minuto.',
        'unexpected'      => 'Risposta inattesa da NEST2BEND.',
        'step_unreadable' => 'Il file STEP non è stato trovato sul server.',
        'step_too_large'  => 'Il file STEP supera i 20 MB.',
        'origin_mismatch' => 'NEST2BEND ha risposto da :received, ma l\'URL inserito nell\'ERP è :configured. I due devono essere identici (APP_URL di NEST2BEND).',
    ],

    // Écran presse plieuse (methods.tool.punch-designer) quand NEST2BEND est relié
    'panel' => [
        'title'     => 'NEST2BEND è collegato all\'ERP',
        'lead'      => 'La simulazione di piegatura si avvia da una riga di preventivo i cui documenti contengono il file STEP del pezzo (pulsante « Simula la piegatura »).',
        'expired'   => 'L\'abbonamento NEST2BEND è scaduto: le simulazioni sono sospese.',
    ],

    // Postes de charge
    'is_bending' => 'Centro di lavoro di piegatura (pressa piegatrice)',
];
