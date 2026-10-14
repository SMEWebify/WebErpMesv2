<?php

namespace App\Support;

use App\Models\Workflow\OrderLineDelayHistory;

/**
 * Origine et motif d'un report, transmis à OrderLinesObserver par le code
 * appelant : l'observer voit le modèle, pas la requête qui l'a modifié.
 *
 *     OrderLineDelayContext::run(OrderLineDelayHistory::ORIGIN_ORDER, fn () => $line->update(...));
 *
 * Sans contexte explicite : « api » pour une route /api, « system » en
 * console (commande, job), « manual » sinon.
 */
final class OrderLineDelayContext
{
    private static ?string $origin = null;
    private static ?string $reason = null;

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(string $origin, callable $callback, ?string $reason = null): mixed
    {
        [$previousOrigin, $previousReason] = [self::$origin, self::$reason];
        self::$origin = $origin;
        self::$reason = self::normalizeReason($reason);

        try {
            return $callback();
        } finally {
            [self::$origin, self::$reason] = [$previousOrigin, $previousReason];
        }
    }

    public static function origin(): string
    {
        if (self::$origin !== null) {
            return self::$origin;
        }
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return OrderLineDelayHistory::ORIGIN_SYSTEM;
        }
        if (app()->bound('request') && request()->is('api/*', '*/api/*')) {
            return OrderLineDelayHistory::ORIGIN_API;
        }

        return OrderLineDelayHistory::ORIGIN_MANUAL;
    }

    public static function reason(): ?string
    {
        return self::$reason;
    }

    /** Le motif est facultatif et ne doit jamais faire échouer l'enregistrement : il est tronqué, pas refusé. */
    public static function normalizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : mb_substr($reason, 0, 255);
    }
}
