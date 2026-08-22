<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Leave reference period
    |--------------------------------------------------------------------------
    |
    | Start of the period entitlements are granted on. The French paid-leave
    | year runs from 1 June to 31 May; set month to 1 for a calendar year.
    |
    */

    'leave_period_start_month' => (int) env('HR_LEAVE_PERIOD_START_MONTH', 6),
    'leave_period_start_day'   => (int) env('HR_LEAVE_PERIOD_START_DAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Daily working hours
    |--------------------------------------------------------------------------
    |
    | Fallback used to convert an absence entered in hours into days when the
    | employee has no active contract carrying a weekly duration.
    |
    */

    'default_daily_hours' => (float) env('HR_DEFAULT_DAILY_HOURS', 7),

    /*
    |--------------------------------------------------------------------------
    | Authorisation expiry warning
    |--------------------------------------------------------------------------
    |
    | How many days before its expiry date a training is flagged as expiring on
    | the versatility matrix. Informative only: nothing is ever blocked.
    |
    */

    'habilitation_warning_days' => (int) env('HR_HABILITATION_WARNING_DAYS', 60),

];
