<?php

namespace App\Support;

use App\Services\Settings\SettingsService;

/**
 * Unité d'affichage des temps opératoires (heures ou minutes).
 *
 * Le stockage reste en HEURES partout (colonnes decimal(10,3), API, exports,
 * capacités) : cette classe ne fait que convertir aux bords, côté PHP (Blade,
 * PDF). L'équivalent côté React est resources/js/lib/duration.js ; les deux
 * appliquent la même règle (config/display.php).
 */
class TimeDisplay
{
    public const SETTING_KEY = 'display.time_unit';

    public const HOURS = 'hours';

    public const MINUTES = 'minutes';

    /** Décimales du stockage en heures (colonnes decimal(10,3)). */
    public const STORED_DECIMALS = 3;

    /**
     * @return string 'hours' ou 'minutes' (toute autre valeur retombe sur heures)
     */
    public static function unit(): string
    {
        $value = app(SettingsService::class)->get(self::SETTING_KEY, config('display.time_unit', self::HOURS));

        return $value === self::MINUTES ? self::MINUTES : self::HOURS;
    }

    public static function isMinutes(): bool
    {
        return self::unit() === self::MINUTES;
    }

    public static function minutesDecimals(): int
    {
        return max(0, (int) config('display.minutes_decimals', 1));
    }

    public static function hoursToMinutes(float|int|string|null $hours): float
    {
        return round(((float) $hours) * 60, self::minutesDecimals());
    }

    /** Saisie en minutes → valeur stockée en heures (3 décimales). */
    public static function minutesToHours(float|int|string|null $minutes): float
    {
        return round(((float) $minutes) / 60, self::STORED_DECIMALS);
    }

    /**
     * Texte d'un temps stocké en heures, dans l'unité du réglage.
     * Heures : valeur telle que stockée (comportement historique) ; minutes :
     * arrondi de config('display.minutes_decimals'), zéros de fin retirés.
     */
    public static function format(float|int|string|null $hours, ?string $unit = null): string
    {
        $unit ??= self::unit();

        if ($unit === self::MINUTES) {
            $minutes = self::hoursToMinutes($hours);

            return rtrim(rtrim(number_format($minutes, self::minutesDecimals(), '.', ''), '0'), '.') . ' min';
        }

        return (string) $hours . ' h';
    }
}
