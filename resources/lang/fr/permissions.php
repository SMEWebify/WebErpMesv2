<?php

/*
 * Description des droits, affichée sous le nom technique dans Votre société →
 * Rôles et permissions. Clé = nom de la permission (spatie). Un droit absent
 * de cette liste (créé à la main) s'affiche sans description.
 */
return [
    'descriptions' => [
        // Menus des modules
        'companies-menu' => 'Menu Sociétés : clients, fournisseurs, contacts et adresses.',
        'leads-menu' => 'Menu Prospects (leads).',
        'opportunities-menu' => 'Menu Opportunités ; affiche aussi les sections opportunité du dossier d\'affaire.',
        'quotes-menu' => 'Menu Devis ; affiche aussi les devis, montants et blocages dans le dossier d\'affaire.',
        'orders-menu' => 'Menu Commandes ; affiche aussi les commandes dans le dossier d\'affaire.',
        'order-confirmations-menu' => 'Entrée Accusés de réception de commande (ARC) du menu Commandes.',
        'scheduling-menu' => 'Menu Planification : planning de charge, liste et statut des tâches, Kanban, Gantt, imbrication.',
        'deliverys-menu' => 'Menu Bons de livraison ; affiche aussi les BL dans le dossier d\'affaire.',
        'returns-menu' => 'Entrée Retours (menus Bons de livraison et Qualité).',
        'invoices-menu' => 'Menu Factures : factures, proformas, avoirs, relances (lecture).',
        'products-menu' => 'Lecture de la base article : liste, fiche, gamme, nomenclature, documents.',
        'products-edit' => 'Écriture de la base article : création, modification, duplication, gamme, nomenclature, fournisseurs, tarifs, import, documents de l\'article.',
        'products-merge' => 'Fusionner deux articles (exige aussi products-edit).',
        'stock-lot-serial-management' => 'Stock : emplacements, mouvements, inventaires, ruptures, lots (coulée, certificat matière) et numéros de série.',
        'purchases-menu' => 'Menu Achats : demandes, demandes de prix, commandes, réceptions, factures fournisseurs.',
        'quality-menu' => 'Menu Qualité : tableau de bord, actions, dérogations, non-conformités, plans qualité, AMDEC, processus.',
        'audit-menu' => 'Audits internes (planification et réalisation).',
        'settings-time-menu' => 'Menu Temps : vue d\'ensemble des temps et équipes (shifts).',
        'methods-menu' => 'Menu Méthodes : postes de charge, ressources, unités, catégories d\'affaire, motifs de perte…',
        'accounting-menu' => 'Menu Comptabilité : moyens de paiement, TVA, imputations, modes de livraison ; exports factures et FEC.',
        'human-resources-menu' => 'Ressources humaines : salariés, notes de frais, présence, soldes de congés, export paie, validation des absences, dossier salarié confidentiel.',
        'osh-menu' => 'Menu Santé et sécurité : incidents, risques, formations, matrice de polyvalence, conformités ; gestion des types d\'habilitation.',
        'documents-menu' => 'Menu Documents.',
        'reports-menu' => 'Menu Rapports (tableau de bord, rapports comptables).',
        'spreadsheet-menu' => 'Menu Tableurs.',
        'your-company-menu' => 'Administration : paramètres de la société, rôles et permissions, utilisateurs, imports / exports, intégrations.',
        'asset_manager' => 'Module GMAO : tableau de bord, équipements, plans de maintenance, ordres de travail.',
        'asset-menu' => 'Entrée Équipements (immobilisations) des menus Comptabilité et GMAO.',

        // Droits d'action
        'accounting-manage' => 'Régimes et natures de TVA, matrices de TVA, verrouillage des périodes comptables.',
        'credit-limit-override' => 'Modifier le plafond d\'encours d\'une société et créer une commande ou un BL au-delà (motif obligatoire).',
        'invoice-reminders-send' => 'Envoyer ou tracer les relances de factures et régler les niveaux de relance.',
        'inspection-plans-edit' => 'Créer et buller les plans de contrôle ; signer « vérifié » un rapport premier article.',
        'inspection-plans-approve' => 'Valider un plan de contrôle ; approuver (et figer) un rapport premier article.',
        'required-operations-override' => 'Convertir un devis en commande malgré une opération exigée par la catégorie d\'affaire absente de la gamme (motif obligatoire).',
        'audit-access-log' => 'Consulter et exporter le journal des consultations (qui a ouvert quelle fiche).',
        'quality-derogation-validate' => 'Passer une dérogation en « Validé » et modifier une dérogation validée.',
        'production-release' => 'Donner ou annuler le bon pour fabrication (BPF) des lignes de commande.',
        'purchase-quality-clauses-manage' => 'Gérer le référentiel des clauses qualité des commandes d\'achat et leurs règles.',
        'task-release' => 'Viser la libération d\'une phase terminée (postes avec « Visa de libération requis »).',
        'dossier-validate' => 'Valider, annuler, exiger ou lever le dossier de fin de fabrication d\'une ligne de commande (débloque BL et facture).',
        'reception-document-check' => 'Viser la conformité documentaire (certificats) des lignes de réception.',
        'unqualified-supplier-override' => 'Créer ou valider une commande d\'achat chez un fournisseur non qualifié (motif obligatoire).',
        'contract-review-approve' => 'Viser (valider ou rejeter) la revue de contrat d\'une commande et ses avenants ; annuler un avenant.',
        'order-price-recalculate' => 'Activer le prix calculé d\'une ligne de commande sortie de préparation (motif obligatoire).',
    ],
];
