<?php

return [
    // Écran de configuration
    'title'          => 'NEST2BEND',
    'subtitle'       => 'simulation de pliage sur presse plieuse',
    'config'         => 'Liaison avec NEST2BEND',
    'base_url'       => 'URL de NEST2BEND',
    'base_url_hint'  => 'Par exemple https://app.nest2bend.fr',
    'api_key'        => 'Clé API',
    'api_key_hint'   => 'Clé n2b_… créée dans NEST2BEND. Elle est chiffrée ici et n\'est plus jamais affichée.',
    'api_key_keep'   => 'laisser vide pour conserver',
    'clear_key'      => 'Supprimer la clé (retour à l\'écran commercial)',
    'save'           => 'Enregistrer',
    'saved'          => 'Liaison NEST2BEND enregistrée.',
    'test_button'    => 'Tester la connexion',
    'test_running'   => 'Test en cours…',
    'test_ok'        => 'Connexion réussie : :societe.',
    'test_network_error' => 'Le test n\'a pas abouti :',
    'open_app'       => 'Ouvrir NEST2BEND',
    'configure'      => 'Configurer',
    'card_lead'      => 'Simulation de pliage en iframe depuis les lignes de devis qui ont un fichier STEP.',
    'origin_title'   => 'Origine à déclarer dans NEST2BEND',
    'origin_intro'   => 'NEST2BEND n\'accepte d\'être affiché que dans les origines qu\'il connaît. Déclarez celle-ci (schéma, hôte et port, sans chemin) :',
    'what_title'     => 'Comment ça marche',
    'what_1'         => 'Sur une ligne de devis dont la GED contient un fichier STEP, le bouton « Simuler » ouvre NEST2BEND dans l\'ERP.',
    'what_2'         => 'Le fichier part du serveur de l\'ERP ; la clé API ne quitte jamais le serveur.',
    'what_3'         => 'L\'utilisateur relit la pièce et la simulation dans NEST2BEND, sans s\'y connecter.',
    'what_4'         => 'Le temps retenu (préparation et temps unitaire) est ajouté au commentaire interne de la ligne.',
    'checked_at'     => 'Dernier contrôle',
    'never_checked'  => 'jamais',
    'societe'        => 'Société',
    'etat'           => 'Abonnement',
    'echeance'       => 'Échéance',
    'modules'        => 'Modules',
    'presses_max'    => 'Presses',
    'last_error'     => 'Dernier incident',

    'badge' => [
        'none'    => 'Non configuré',
        'unknown' => 'À tester',
        'invalid' => 'Clé refusée',
        'expired' => 'Abonnement expiré',
        'active'  => 'Actif',
    ],

    'state' => [
        'none'    => 'Aucune clé NEST2BEND n\'est enregistrée.',
        'unknown' => 'La clé n\'a pas encore pu être vérifiée : testez la connexion.',
        'invalid' => 'Clé invalide ou révoquée.',
        'expired' => 'Abonnement NEST2BEND expiré.',
        'warning' => 'Abonnement NEST2BEND en :etat jusqu\'au :echeance.',
    ],

    'etats' => [
        'actif'         => 'cours',
        'essai'         => 'période d\'essai',
        'grace'         => 'période de grâce',
        'lecture_seule' => 'lecture seule',
        ''              => '—',
    ],

    // Bouton « Simuler » (ligne de devis)
    'button' => [
        'label'      => 'Simuler le pliage',
        'starting'   => 'Ouverture de NEST2BEND…',
        'saving'     => 'Enregistrement du temps…',
        'no_step'    => 'Ajoutez le fichier STEP de la pièce dans les documents de la ligne.',
        'step_file'  => 'Fichier envoyé : :name',
        'close'      => 'Fermer',
        'modal_title'=> 'NEST2BEND — simulation de pliage',
        'remarks'    => 'Remarques de NEST2BEND',
        'renew'      => 'Renouveler l\'abonnement',
        'restart'    => 'Relancer',
        'restart_hint' => 'Page introuvable ou simulation perdue ? Ouvre une nouvelle session NEST2BEND.',
    ],

    'result_saved' => 'Temps de pliage ajouté au commentaire interne : :summary',

    // Fiche de réglage PDF (champ `fiche` du résultat), rangée dans la GED de la ligne
    'sheet' => [
        'comment'           => 'Fiche de réglage NEST2BEND (presse plieuse)',
        'stored'            => 'Fiche de réglage rangée dans les documents de la ligne : :name',
        'pending'           => 'Fiche de réglage en cours de récupération…',
        'retry'             => 'Récupérer la fiche',
        'retrying'          => 'Récupération de la fiche…',
        'open'              => 'Ouvrir la fiche',
        'none'              => 'Aucune fiche de réglage à récupérer pour cette ligne.',
        'error_404'         => 'Fiche de réglage indisponible : pièce non simulée, aucun poinçon choisi, ou un outil ou la presse a quitté l\'atelier NEST2BEND depuis la simulation.',
        'error_key'         => 'Fiche de réglage non récupérée : clé API NEST2BEND refusée ou abonnement expiré.',
        'error_unavailable' => 'Fiche de réglage non récupérée : NEST2BEND indisponible. Réessayez plus tard (le résultat reste lisible 30 jours).',
        'error_other'       => 'Fiche de réglage non récupérée (statut :status).',
    ],

    // Dernière simulation de la ligne (resultat.simulation)
    'last' => [
        'title'    => 'Dernière simulation',
        'press'    => 'Presse',
        'tools'    => 'Outils',
        'sequence' => 'Ordre de pliage',
        'sheet'    => 'Fiche de réglage',
        'punch'    => 'Poinçon :tool',
        'die'      => 'matrice :tool',
        'flipped'  => '(retourné)',
    ],

    'comment' => [
        'entry'       => '[NEST2BEND :date] :summary',
        'preparation' => 'Préparation :time',
        'unit'        => ':time/pièce (lot :qty)',
        'thickness'   => 'ép. :t mm',
        'no_time'     => 'pas de temps retenu',
    ],

    'errors' => [
        'unauthorized'    => 'Clé API NEST2BEND invalide ou révoquée.',
        'inactive'        => 'Abonnement NEST2BEND expiré.',
        'not_found'       => 'Résultat NEST2BEND introuvable pour cette ligne.',
        'invalid'         => 'NEST2BEND a refusé la demande.',
        'unavailable'     => 'NEST2BEND momentanément indisponible, réessayez.',
        'rate_limited'    => 'Trop de demandes vers NEST2BEND, réessayez dans une minute.',
        'unexpected'      => 'Réponse inattendue de NEST2BEND.',
        'step_unreadable' => 'Le fichier STEP est introuvable sur le serveur.',
        'step_too_large'  => 'Le fichier STEP dépasse 20 Mo.',
        'origin_mismatch' => 'NEST2BEND a répondu depuis :received, mais l\'URL saisie dans l\'ERP est :configured. Les deux doivent être identiques (APP_URL de NEST2BEND).',
    ],

    // Écran presse plieuse (methods.tool.punch-designer) quand NEST2BEND est relié
    'panel' => [
        'title'     => 'NEST2BEND est relié à l\'ERP',
        'lead'      => 'La simulation de pliage se lance depuis une ligne de devis dont les documents contiennent le fichier STEP de la pièce (bouton « Simuler le pliage »).',
        'expired'   => 'L\'abonnement NEST2BEND est expiré : les simulations sont suspendues.',
    ],

    // Postes de charge
    'is_bending' => 'Poste de pliage (presse plieuse)',
];
