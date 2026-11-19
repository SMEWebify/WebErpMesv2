<?php

// Textes des briques React partagées (DataTable, Pagination, dépôt CAO), posés par le gabarit dans <meta name="ui-common"> (lib/i18n.js, commonText).
return [
    'show_column' => 'Show the column again',
    'hide_column' => 'Hide the column',
    'date_from' => 'From',
    'date_to' => 'To',
    'results' => 'results',
    'result' => 'result',
    'cad_no_file' => 'No recognised file (:formats).',
    'cad_import_error' => 'Error during import.',
    'cad_network_error' => 'Network error while uploading.',
    'cad_importing' => 'Importing…',
    'cad_drop' => 'Drop',
    'cad_help' => 'The dropped drawing is attached to the line in the document manager (except .sym, .drg and .bvc, fully read on import). A .geo also produces an SVG usable for nesting. A RADAN nest (.drg) creates one line per part, quantity = parts per sheet × number of sheets. A BySoft job (.bvc) creates one line per part with its outline as SVG, quantity = job quantity. A .pdf drawing is read by AI (Integrations → AI): check the title block on the created line.',
    'cad_drop_help' => 'files here to create the lines automatically  — or click to select',
];
