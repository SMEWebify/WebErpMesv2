<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Quote follow-up delay
    |--------------------------------------------------------------------------
    |
    | Number of days after a quote was sent (last e-mail, otherwise last
    | update) before the affair summary flags it as "sent without answer".
    |
    */

    'quote_follow_up_days' => (int) env('AFFAIRS_QUOTE_FOLLOW_UP_DAYS', 10),

    /*
    |--------------------------------------------------------------------------
    | Unvalidated visit delay
    |--------------------------------------------------------------------------
    |
    | Number of hours a site visit may stay a draft before it is flagged.
    | The report is usually validated the same day; two days leaves room
    | for a visit done late in the afternoon.
    |
    */

    'visit_draft_hours' => (int) env('AFFAIRS_VISIT_DRAFT_HOURS', 48),

];
