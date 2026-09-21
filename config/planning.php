<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inter-operation delay (default, hours)
    |--------------------------------------------------------------------------
    |
    | Buffer added between two consecutive productive tasks of the same order
    | line when no pair-specific override exists in `operation_transition_delays`.
    | Applied as workshop hours (skips nights, week-ends, bank holidays), like
    | task duration itself. Set 0 to keep the historical "instant transition"
    | behaviour.
    |
    */

    'inter_operation_hours' => (float) env('PLANNING_INTER_OPERATION_HOURS', 0),

    /*
    |--------------------------------------------------------------------------
    | Minimum gap between operations (hours)
    |--------------------------------------------------------------------------
    |
    | Enforced minimum gap between the end of a task and the start of the next
    | one, independent of the pair-specific transfer delay. Useful when the
    | shop needs at least a fixed buffer (e.g. daily dispatch round) regardless
    | of the operation involved. Set 0 to disable.
    |
    */

    'min_operation_gap_hours' => (float) env('PLANNING_MIN_OPERATION_GAP_HOURS', 0),

];
