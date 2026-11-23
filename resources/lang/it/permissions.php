<?php

/*
 * Description des droits, affichée sous le nom technique dans Votre société →
 * Rôles et permissions. Clé = nom de la permission (spatie). Un droit absent
 * de cette liste (créé à la main) s'affiche sans description.
 */
return [
    'descriptions' => [
        // Menus des modules
        'companies-menu' => 'Menu Aziende: clienti, fornitori, contatti e indirizzi.',
        'leads-menu' => 'Menu Potenziali clienti (lead).',
        'opportunities-menu' => 'Menu Opportunità; mostra anche le sezioni opportunità del fascicolo di commessa.',
        'quotes-menu' => 'Menu Preventivi; mostra anche preventivi, importi e blocchi nel fascicolo di commessa.',
        'orders-menu' => 'Menu Ordini; mostra anche gli ordini nel fascicolo di commessa.',
        'order-confirmations-menu' => 'Voce Conferme d\'ordine del menu Ordini.',
        'scheduling-menu' => 'Menu Pianificazione: piano di carico, elenco e stato delle fasi, Kanban, Gantt, nesting.',
        'deliverys-menu' => 'Menu DDT; mostra anche i DDT nel fascicolo di commessa.',
        'returns-menu' => 'Voce Resi (menu DDT e Qualità).',
        'invoices-menu' => 'Menu Fatture: fatture, proforma, note di credito, solleciti (lettura).',
        'products-menu' => 'Lettura dell\'anagrafica articoli: elenco, scheda, ciclo di lavorazione, distinta base, documenti.',
        'products-edit' => 'Scrittura dell\'anagrafica articoli: creazione, modifica, duplicazione, ciclo di lavorazione, distinta base, fornitori, listini, importazione, documenti dell\'articolo.',
        'products-merge' => 'Unire due articoli (richiede anche products-edit).',
        'stock-lot-serial-management' => 'Magazzino: ubicazioni, movimenti, inventari, rotture di stock, lotti (colata, certificato materiale) e numeri di serie.',
        'purchases-menu' => 'Menu Acquisti: richieste, richieste d\'offerta, ordini, ricevimenti, fatture fornitori.',
        'quality-menu' => 'Menu Qualità: cruscotto, azioni, deroghe, non conformità, piani qualità, FMEA, processi.',
        'audit-menu' => 'Audit interni (pianificazione ed esecuzione).',
        'settings-time-menu' => 'Menu Tempi: panoramica dei tempi e delle squadre (turni).',
        'methods-menu' => 'Menu Metodi: centri di lavoro, risorse, unità, categorie di commessa, motivi di perdita…',
        'accounting-menu' => 'Menu Contabilità: mezzi di pagamento, IVA, imputazioni, modalità di consegna; esportazioni fatture e FEC.',
        'human-resources-menu' => 'Risorse umane: dipendenti, note spese, presenze, saldi ferie, esportazione paghe, approvazione delle assenze, fascicolo riservato del dipendente.',
        'osh-menu' => 'Menu Salute e sicurezza: incidenti, rischi, formazioni, matrice delle competenze, conformità; gestione dei tipi di abilitazione.',
        'documents-menu' => 'Menu Documenti.',
        'reports-menu' => 'Menu Report (cruscotto, report contabili).',
        'spreadsheet-menu' => 'Menu Fogli di calcolo.',
        'your-company-menu' => 'Amministrazione: parametri dell\'azienda, ruoli e permessi, utenti, importazioni / esportazioni, integrazioni.',
        'asset_manager' => 'Modulo manutenzione (CMMS): cruscotto, attrezzature, piani di manutenzione, ordini di lavoro.',
        'asset-menu' => 'Voce Attrezzature (immobilizzazioni) dei menu Contabilità e Manutenzione.',

        // Droits d'action
        'accounting-manage' => 'Regimi e nature IVA, matrici IVA, blocco dei periodi contabili.',
        'credit-limit-override' => 'Modificare il fido di un\'azienda e creare un ordine o un DDT oltre tale limite (motivo obbligatorio).',
        'invoice-reminders-send' => 'Inviare o registrare i solleciti delle fatture e impostare i livelli di sollecito.',
        'inspection-plans-edit' => 'Creare i piani di controllo e apporre i palloncini; firmare come «verificato» un rapporto di primo articolo.',
        'inspection-plans-approve' => 'Approvare un piano di controllo; approvare (e bloccare) un rapporto di primo articolo.',
        'required-operations-override' => 'Convertire un preventivo in ordine nonostante l\'assenza dal ciclo di lavorazione di un\'operazione richiesta dalla categoria di commessa (motivo obbligatorio).',
        'audit-access-log' => 'Consultare ed esportare il registro delle consultazioni (chi ha aperto quale scheda).',
        'quality-derogation-validate' => 'Portare una deroga a «Approvata» e modificare una deroga approvata.',
        'production-release' => 'Concedere o annullare il rilascio per la produzione delle righe d\'ordine.',
        'purchase-quality-clauses-manage' => 'Gestire il repertorio delle clausole qualità degli ordini d\'acquisto e le relative regole.',
        'task-release' => 'Vistare il rilascio di una fase terminata (centri di lavoro con «Visto di rilascio richiesto»).',
        'dossier-validate' => 'Convalidare, annullare, richiedere o revocare il fascicolo di fine produzione di una riga d\'ordine (sblocca DDT e fattura).',
        'reception-document-check' => 'Vistare la conformità documentale (certificati) delle righe di ricevimento.',
        'unqualified-supplier-override' => 'Creare o convalidare un ordine d\'acquisto presso un fornitore non qualificato (motivo obbligatorio).',
        'contract-review-approve' => 'Vistare (approvare o respingere) il riesame del contratto di un ordine e le sue varianti; annullare una variante.',
        'order-price-recalculate' => 'Attivare il prezzo calcolato di una riga d\'ordine uscita dalla preparazione (motivo obbligatorio).',
        'affair-risk-close' => 'Chiudere un rischio critico del registro dei rischi di una commessa.',
        'quality-rework-order' => 'Avviare un ordine di rilavorazione da una non conformità (fasi sulla riga d\'ordine) e posticipare la data di consegna della riga.',
        'workshop-kiosk' => 'Account di postazione d\'officina condiviso: apre solo la schermata di scansione, e ogni timbratura richiede il badge di un operatore. Da assegnare solo a un ruolo dedicato «Postazione officina».',
    ],
];
