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

    /*
    |--------------------------------------------------------------------------
    | Risk register
    |--------------------------------------------------------------------------
    |
    | Categories offered for an affair risk (labels in affairs.risks.categories)
    | and the criticality (probability × severity, both 1-4) from which an
    | active risk is critical: flagged in the summary, and closing it requires
    | the `affair-risk-close` permission.
    |
    */

    'risks' => [
        'categories' => ['technical', 'schedule', 'quality', 'supply', 'contractual', 'safety'],
        'critical_threshold' => (int) env('AFFAIRS_RISK_CRITICAL_THRESHOLD', 9),
    ],

];
