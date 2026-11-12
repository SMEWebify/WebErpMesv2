<?php

return [
    // Settings screen
    'title'          => 'NEST2BEND',
    'subtitle'       => 'press brake bending simulation',
    'config'         => 'NEST2BEND link',
    'base_url'       => 'NEST2BEND URL',
    'base_url_hint'  => 'For example https://app.nest2bend.fr',
    'api_key'        => 'API key',
    'api_key_hint'   => 'n2b_… key created in NEST2BEND. It is encrypted here and never shown again.',
    'api_key_keep'   => 'leave empty to keep it',
    'clear_key'      => 'Delete the key (back to the commercial screen)',
    'save'           => 'Save',
    'saved'          => 'NEST2BEND link saved.',
    'test_button'    => 'Test the connection',
    'test_running'   => 'Testing…',
    'test_ok'        => 'Connection successful: :societe.',
    'test_network_error' => 'The test failed:',
    'open_app'       => 'Open NEST2BEND',
    'configure'      => 'Configure',
    'card_lead'      => 'Bending simulation in an iframe, from quote lines that have a STEP file.',
    'origin_title'   => 'Origin to declare in NEST2BEND',
    'origin_intro'   => 'NEST2BEND only allows itself to be displayed in the origins it knows. Declare this one (scheme, host and port, no path):',
    'what_title'     => 'How it works',
    'what_1'         => 'On a quote line whose documents contain a STEP file, the "Simulate" button opens NEST2BEND inside the ERP.',
    'what_2'         => 'The file is sent by the ERP server; the API key never leaves the server.',
    'what_3'         => 'The user reviews the part and the simulation in NEST2BEND, without logging in.',
    'what_4'         => 'The retained time (setup and unit time) is appended to the internal comment of the line.',
    'checked_at'     => 'Last check',
    'never_checked'  => 'never',
    'societe'        => 'Company',
    'etat'           => 'Subscription',
    'echeance'       => 'Due date',
    'modules'        => 'Modules',
    'presses_max'    => 'Press brakes',
    'last_error'     => 'Last incident',

    'badge' => [
        'none'    => 'Not configured',
        'unknown' => 'To be tested',
        'invalid' => 'Key rejected',
        'expired' => 'Subscription expired',
        'active'  => 'Active',
    ],

    'state' => [
        'none'    => 'No NEST2BEND key is saved.',
        'unknown' => 'The key could not be checked yet: test the connection.',
        'invalid' => 'Invalid or revoked key.',
        'expired' => 'NEST2BEND subscription expired.',
        'warning' => 'NEST2BEND subscription in :etat until :echeance.',
    ],

    'etats' => [
        'actif'         => 'progress',
        'essai'         => 'trial period',
        'grace'         => 'grace period',
        'lecture_seule' => 'read-only',
        ''              => '—',
    ],

    'button' => [
        'label'      => 'Simulate bending',
        'starting'   => 'Opening NEST2BEND…',
        'saving'     => 'Saving the time…',
        'no_step'    => 'Add the STEP file of the part to the documents of the line.',
        'step_file'  => 'File sent: :name',
        'close'      => 'Close',
        'modal_title'=> 'NEST2BEND — bending simulation',
        'remarks'    => 'NEST2BEND remarks',
        'renew'      => 'Renew the subscription',
        'restart'    => 'Restart',
        'restart_hint' => 'Page not found or simulation lost? Opens a new NEST2BEND session.',
    ],

    'result_saved' => 'Bending time appended to the internal comment: :summary',

    'comment' => [
        'entry'       => '[NEST2BEND :date] :summary',
        'preparation' => 'Setup :time',
        'unit'        => ':time/part (batch :qty)',
        'thickness'   => 'th. :t mm',
        'no_time'     => 'no retained time',
    ],

    'errors' => [
        'unauthorized'    => 'Invalid or revoked NEST2BEND API key.',
        'inactive'        => 'NEST2BEND subscription expired.',
        'not_found'       => 'NEST2BEND result not found for this line.',
        'invalid'         => 'NEST2BEND rejected the request.',
        'unavailable'     => 'NEST2BEND temporarily unavailable, please retry.',
        'rate_limited'    => 'Too many requests to NEST2BEND, please retry in a minute.',
        'unexpected'      => 'Unexpected answer from NEST2BEND.',
        'step_unreadable' => 'The STEP file cannot be found on the server.',
        'step_too_large'  => 'The STEP file exceeds 20 MB.',
        'origin_mismatch' => 'NEST2BEND answered from :received, but the URL saved in the ERP is :configured. Both must be identical (NEST2BEND APP_URL).',
    ],

    'panel' => [
        'title'     => 'NEST2BEND is linked to the ERP',
        'lead'      => 'Bending simulation starts from a quote line whose documents contain the STEP file of the part ("Simulate bending" button).',
        'expired'   => 'The NEST2BEND subscription has expired: simulations are suspended.',
    ],

    'is_bending' => 'Bending workstation (press brake)',
];
