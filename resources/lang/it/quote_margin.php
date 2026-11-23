<?php

return [
    'title' => 'Margine (interno)',
    'internal' => 'Informazione interna: mai stampata sui documenti del cliente.',
    'cost_total' => 'Costo di produzione',
    'sale_total' => 'Prezzo di vendita imponibile',
    'margin' => 'Margine',
    'margin_rate' => 'Margine / costo',
    'markup_rate' => 'Margine / prezzo di vendita',
    'column' => 'Margine',
    'column_toggle' => 'Mostra la colonna margine',
    'unit_cost' => 'Costo unitario',
    'no_cost' => 'Nessun costo di produzione (nessuna operazione, distinta base né sottoassieme valorizzato)',
    'below_threshold' => 'Margine sotto la soglia del :threshold %',
    'lines_without_cost' => '{1} :count riga senza costo di produzione: conteggiata al prezzo di vendita, senza costo.|[2,*] :count righe senza costo di produzione: conteggiate al prezzo di vendita, senza costo.',
    'cost_help' => 'Costo = operazioni (conto lavoro compreso) + distinta base + sottoassiemi. Righe di sezione, subtotale e testo ignorate.',

    'apply_title' => 'Applica un margine globale',
    'apply_rate' => 'Percentuale di margine sul costo (%)',
    'apply_button' => 'Ricalcola i prezzi',
    'apply_help' => 'Prezzo di vendita di ogni riga = costo × (1 + percentuale), sconto della riga compensato. Il prezzo calcolato dal ciclo di lavorazione viene disattivato. Righe senza costo invariate.',
    'apply_confirm' => 'Ricalcolare il prezzo di vendita di tutte le righe a partire dal loro costo?',
    'applied' => '{0} Nessun prezzo ricalcolato.|{1} :count prezzo ricalcolato.|[2,*] :count prezzi ricalcolati.',
    'applied_skipped' => ':count riga/e senza costo lasciata/e al proprio prezzo.',
    'refused_converted' => 'Questo preventivo è stato convertito in ordine: i suoi prezzi sono bloccati, il margine globale non può più essere applicato.',
    'refused_status' => 'Solo un preventivo aperto può essere ricalcolato.',
    'locked_converted' => 'Preventivo convertito in ordine: prezzi bloccati.',
];
