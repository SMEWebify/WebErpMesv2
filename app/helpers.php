<?php

if (!function_exists('format_qty')) {
    /**
     * Format a quantity: remove trailing decimal zeros.
     * 3.000 → "3"   |   1.500 → "1.5"   |   1.125 → "1.125"
     */
    function format_qty($value): string
    {
        return rtrim(rtrim(number_format((float) ($value ?? 0), 3, '.', ''), '0'), '.');
    }
}
