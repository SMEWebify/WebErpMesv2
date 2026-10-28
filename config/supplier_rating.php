<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rolling window
    |--------------------------------------------------------------------------
    |
    | The supplier score shown on the supplier page and used by the purchase
    | dashboard only counts the ratings (and receipt non-conformities) of the
    | last N months.
    |
    */

    'window_months' => (int) env('SUPPLIER_RATING_WINDOW_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | Quality score of a receipt with a non-conformity
    |--------------------------------------------------------------------------
    |
    | A receipt with a line linked to a non-conformity (not canceled) counts
    | this quality score (1 to 5), whatever was entered in the quick rating.
    | A receipt with an NC but no rating still counts once with this score.
    |
    */

    'nc_quality_score' => 1,

    /*
    |--------------------------------------------------------------------------
    | Suggested delay score
    |--------------------------------------------------------------------------
    |
    | Days late (receipt date against the promised date of the purchase line,
    | worst line of the receipt) => suggested score. The first threshold the
    | delay fits under wins; anything later gets 1. Only a suggestion: the user
    | changes it in the rating window.
    |
    */

    'delay_thresholds' => [
        0  => 5,
        2  => 4,
        7  => 3,
        14 => 2,
    ],

];
