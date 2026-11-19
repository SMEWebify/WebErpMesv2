<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demandes entrantes (pistes créées depuis un e-mail)
    |--------------------------------------------------------------------------
    |
    | Un e-mail déposé dans l'ERP (.eml / .msg) ou transmis par n8n à l'API
    | `api.leads.inbound` devient une piste. L'expéditeur est rapproché d'un
    | contact par son adresse, sinon d'une société par le domaine de l'adresse ;
    | à défaut, société, contact et adresse sont créés (prospect à compléter).
    |
    */
    'inbound' => [
        // Valeur du champ « Source » des pistes créées depuis un e-mail.
        'source' => env('LEADS_INBOUND_SOURCE', 'E-mail'),

        // Responsable des pistes reçues par l'API quand l'appel n'en désigne pas
        // (adresse e-mail d'un utilisateur ; vide = piste non attribuée).
        'default_owner_email' => env('LEADS_INBOUND_OWNER_EMAIL'),

        // Domaines de messagerie publics : jamais utilisés pour retrouver une société.
        'public_domains' => [
            'gmail.com', 'googlemail.com', 'outlook.com', 'outlook.fr', 'hotmail.com', 'hotmail.fr',
            'live.com', 'live.fr', 'msn.com', 'yahoo.com', 'yahoo.fr', 'icloud.com', 'me.com',
            'orange.fr', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'neuf.fr', 'laposte.net', 'bbox.fr',
            'aol.com', 'gmx.fr', 'gmx.com', 'proton.me', 'protonmail.com',
        ],

        // Taille maximale d'un e-mail déposé ou d'une pièce jointe reçue (Ko).
        'max_kilobytes' => 20480,

        // Texte du message repris dans le commentaire de la piste (caractères).
        'max_body_chars' => 20000,
    ],
];
