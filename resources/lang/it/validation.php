<?php

return [

/*
    |--------------------------------------------------------------------------
    | Lignes de Langue pour la Validation
    |--------------------------------------------------------------------------
    |
    | Les lignes de langue suivantes contiennent les messages d'erreur par défaut utilisés par
    | la classe de validation. Certaines de ces règles ont plusieurs versions telles
    | que les règles de taille. N'hésitez pas à ajuster chacun de ces messages ici.
    |
    */

    'accepted' => 'Il campo :attribute deve essere accettato.',
    'accepted_if' => 'Il campo :attribute deve essere accettato quando :other è :value.',
    'active_url' => 'Il campo :attribute non è un URL valido.',
    'after' => 'Il campo :attribute deve essere una data successiva al :date.',
    'after_or_equal' => 'Il campo :attribute deve essere una data successiva o uguale al :date.',
    'alpha' => 'Il campo :attribute può contenere solo lettere.',
    'alpha_dash' => 'Il campo :attribute può contenere solo lettere, numeri, trattini e trattini bassi.',
    'alpha_num' => 'Il campo :attribute può contenere solo lettere e numeri.',
    'array' => 'Il campo :attribute deve essere un array.',
    'before' => 'Il campo :attribute deve essere una data precedente al :date.',
    'before_or_equal' => 'Il campo :attribute deve essere una data precedente o uguale al :date.',
    'between' => [
        'array' => 'Il campo :attribute deve avere tra :min e :max elementi.',
        'file' => 'Il campo :attribute deve essere tra :min e :max kilobyte.',
        'numeric' => 'Il campo :attribute deve essere tra :min e :max.',
        'string' => 'Il campo :attribute deve essere tra :min e :max caratteri.',
    ],
    'boolean' => 'Il campo :attribute deve essere vero o falso.',
    'confirmed' => 'La conferma del campo :attribute non corrisponde.',
    'current_password' => 'La password non è corretta.',
    'date' => 'Il campo :attribute non è una data valida.',
    'date_equals' => 'Il campo :attribute deve essere una data uguale a :date.',
    'date_format' => 'Il campo :attribute non corrisponde al formato :format.',
    'declined' => 'Il campo :attribute deve essere rifiutato.',
    'declined_if' => 'Il campo :attribute deve essere rifiutato quando :other è :value.',
    'different' => 'I campi :attribute e :other devono essere diversi.',
    'digits' => 'Il campo :attribute deve essere di :digits cifre.',
    'digits_between' => 'Il campo :attribute deve essere tra :min e :max cifre.',
    'dimensions' => 'Il campo :attribute ha dimensioni dell\'immagine non valide.',
    'distinct' => 'Il campo :attribute ha un valore duplicato.',
    'doesnt_end_with' => 'Il campo :attribute non deve terminare con uno dei seguenti valori: :values.',
    'doesnt_start_with' => 'Il campo :attribute non deve iniziare con uno dei seguenti valori: :values.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'ends_with' => 'Il campo :attribute deve terminare con uno dei seguenti valori: :values.',
    'enum' => 'Il valore selezionato per :attribute non è valido.',
    'exists' => 'Il valore selezionato per :attribute non è valido.',
    'file' => 'Il campo :attribute deve essere un file.',
    'filled' => 'Il campo :attribute deve contenere un valore.',
    'gt' => [
        'array' => 'Il campo :attribute deve contenere più di :value elementi.',
        'file' => 'Il campo :attribute deve essere maggiore di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore di :value.',
        'string' => 'Il campo :attribute deve contenere più di :value caratteri.',
    ],
    'gte' => [
        'array' => 'Il campo :attribute deve contenere :value elementi o più.',
        'file' => 'Il campo :attribute deve essere maggiore o uguale a :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere maggiore o uguale a :value.',
        'string' => 'Il campo :attribute deve contenere almeno :value caratteri.',
    ],
    'image' => 'Il campo :attribute deve essere un\'immagine.',
    'in' => 'Il valore selezionato per :attribute non è valido.',
    'in_array' => 'Il campo :attribute non esiste in :other.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'ip' => 'Il campo :attribute deve essere un indirizzo IP valido.',
    'ipv4' => 'Il campo :attribute deve essere un indirizzo IPv4 valido.',
    'ipv6' => 'Il campo :attribute deve essere un indirizzo IPv6 valido.',
    'json' => 'Il campo :attribute deve essere una stringa JSON valida.',
    'lowercase' => 'Il campo :attribute deve essere in minuscolo.',
    'lt' => [
        'array' => 'Il campo :attribute deve contenere meno di :value elementi.',
        'file' => 'Il campo :attribute deve essere minore di :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore di :value.',
        'string' => 'Il campo :attribute deve contenere meno di :value caratteri.',
    ],
    'lte' => [
        'array' => 'Il campo :attribute non deve contenere più di :value elementi.',
        'file' => 'Il campo :attribute deve essere minore o uguale a :value kilobyte.',
        'numeric' => 'Il campo :attribute deve essere minore o uguale a :value.',
        'string' => 'Il campo :attribute deve contenere al massimo :value caratteri.',
    ],
    'mac_address' => 'Il campo :attribute deve essere un indirizzo MAC valido.',
    'max' => [
        'array' => 'Il campo :attribute non deve avere più di :max elementi.',
        'file' => 'Il campo :attribute non deve superare :max kilobyte.',
        'numeric' => 'Il campo :attribute non deve essere maggiore di :max.',
        'string' => 'Il campo :attribute non deve superare :max caratteri.',
    ],
    'max_digits' => 'Il campo :attribute non deve avere più di :max cifre.',
    'mimes' => 'Il campo :attribute deve essere un file di tipo: :values.',
    'mimetypes' => 'Il campo :attribute deve essere un file di tipo: :values.',
    'min' => [
        'array' => 'Il campo :attribute deve avere almeno :min elementi.',
        'file' => 'Il campo :attribute deve essere almeno di :min kilobyte.',
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'string' => 'Il campo :attribute deve essere almeno di :min caratteri.',
    ],
    'min_digits' => 'Il campo :attribute deve avere almeno :min cifre.',
    'multiple_of' => 'Il campo :attribute deve essere un multiplo di :value.',
    'not_in' => 'Il valore selezionato per :attribute non è valido.',
    'not_regex' => 'Il formato del campo :attribute non è valido.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'password' => [
        'letters' => 'Il campo :attribute deve contenere almeno una lettera.',
        'mixed' => 'Il campo :attribute deve contenere almeno una lettera maiuscola e una minuscola.',
        'numbers' => 'Il campo :attribute deve contenere almeno un numero.',
        'symbols' => 'Il campo :attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Il valore di :attribute è comparso in una fuga di dati. Scegli un :attribute diverso.',
    ],
    'present' => 'Il campo :attribute deve essere presente.',
    'prohibited' => 'Il campo :attribute non è consentito.',
    'prohibited_if' => 'Il campo :attribute non è consentito quando :other è :value.',
    'prohibited_unless' => 'Il campo :attribute non è consentito a meno che :other sia in :values.',
    'prohibits' => 'Il campo :attribute impedisce la presenza di :other.',
    'regex' => 'Il formato del campo :attribute non è valido.',
    'required' => 'Il campo :attribute è obbligatorio.',
    'required_array_keys' => 'Il campo :attribute deve contenere voci per: :values.',
    'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
    'required_if_accepted' => 'Il campo :attribute è obbligatorio quando :other è accettato.',
    'required_unless' => 'Il campo :attribute è obbligatorio a meno che :other sia in :values.',
    'required_with' => 'Il campo :attribute è obbligatorio quando :values è presente.',
    'required_with_all' => 'Il campo :attribute è obbligatorio quando :values sono presenti.',
    'required_without' => 'Il campo :attribute è obbligatorio quando :values non è presente.',
    'required_without_all' => 'Il campo :attribute è obbligatorio quando nessuno di :values è presente.',
    'same' => 'I campi :attribute e :other devono coincidere.',
    'size' => [
        'array' => 'Il campo :attribute deve contenere :size elementi.',
        'file' => 'Il campo :attribute deve essere di :size kilobyte.',
        'numeric' => 'Il campo :attribute deve essere :size.',
        'string' => 'Il campo :attribute deve essere di :size caratteri.',
    ],
    'starts_with' => 'Il campo :attribute deve iniziare con uno dei seguenti valori: :values.',
    'string' => 'Il campo :attribute deve essere una stringa.',
    'timezone' => 'Il campo :attribute deve essere un fuso orario valido.',
    'unique' => 'Il valore del campo :attribute è già stato utilizzato.',
    'uploaded' => 'Il caricamento del campo :attribute non è riuscito.',
    'uppercase' => 'Il campo :attribute deve essere in maiuscolo.',
    'url' => 'Il campo :attribute deve essere un URL valido.',
    'uuid' => 'Il campo :attribute deve essere un UUID valido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez spécifier des messages de validation personnalisés pour les attributs en utilisant
    | la convention "attribute.rule" pour nommer les lignes. Cela permet de
    | spécifier rapidement un message personnalisé spécifique pour une règle d'attribut donnée.
    |
    */

    'custom' => [
        'nom-de-l\'attribut' => [
            'nom-de-la-règle' => 'messaggio-personalizzato',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Les lignes de langue suivantes sont utilisées pour remplacer notre espace réservé d'attribut
    | par quelque chose de plus convivial pour le lecteur, comme "Adresse e-mail" au lieu de "email".
    | Cela aide simplement à rendre notre message plus expressif.
    |
    */

    'attributes' => [],

];
