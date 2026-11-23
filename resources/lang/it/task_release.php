<?php

return [
    'title' => 'Benestare di rilascio',
    'requires_release' => 'Benestare di rilascio richiesto',
    'requires_release_help' => 'Una fase terminata su questo centro di lavoro attende il benestare di un responsabile prima di passare alla successiva (semplice avviso, mai un blocco).',

    // États
    'state_to_finish' => 'Benestare da dare una volta terminata la fase',
    'state_awaiting' => 'Terminata, in attesa del benestare',
    'state_released' => 'Rilasciata',
    'badge_awaiting' => 'Da vistare',
    'badge_released' => 'Rilasciata',
    'badge_to_finish' => 'Benestare richiesto',
    'badge_predecessor' => 'Precedente non rilasciata',
    'released_on' => 'Rilasciata il :date da :user',

    // Actions
    'comment' => 'Commento (facoltativo)',
    'release' => 'Vista e rilascia',
    'revoke' => 'Annulla il benestare',
    'revoke_confirm' => 'Annullare il benestare di rilascio di questa fase?',
    'released_ok' => 'Fase rilasciata.',
    'revoked_ok' => 'Benestare annullato.',
    'no_permission' => 'Il benestare viene dato da un titolare del permesso « task-release ».',

    // Avertissement sur la tâche suivante (jamais bloquant)
    'predecessor_warning' => 'L\'operazione precedente :tasks non ha ricevuto il benestare di rilascio.|Le operazioni precedenti :tasks non hanno ricevuto il benestare di rilascio.',
    'start_confirm' => 'Avviare comunque?',

    // Refus
    'forbidden' => 'Non avete il permesso di vistare una fase.',
    'not_required' => 'Il centro di lavoro di questa fase non richiede benestare.',
    'already_released' => 'Questa fase è già rilasciata.',
    'not_finished' => 'La fase deve essere terminata prima di essere vistata.',
    'not_released_yet' => 'Questa fase non ha benestare.',
];
