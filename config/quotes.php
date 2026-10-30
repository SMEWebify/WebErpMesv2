<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Margin alert threshold
    |--------------------------------------------------------------------------
    |
    | Margin rate (in % of the cost price, like methods_services.margin) under
    | which a quote line is highlighted in the lines table and the quote margin
    | card turns red. Internal information only: never printed.
    |
    */

    'margin_alert_threshold' => (float) env('QUOTES_MARGIN_ALERT_THRESHOLD', 0),

];
