<?php

namespace App\Services\Production;

use App\Models\Planning\Task;
use App\Services\N2P\N2PPayloadBuilder;

/**
 * Codes de scan communs ERP / MES Nest2Prod (docs/pointage-scan.md) :
 * opération `N2P:{of_code}*{line_ref}*{operation_code}*{seq}`.
 * Les champs reprennent exactement la clé métier du payload des OF
 * (N2PPayloadBuilder), pour qu'un code imprimé par l'ERP retrouve sa tâche
 * dans l'un ou l'autre système.
 */
class ScanCode
{
    public const OPERATION_PREFIX = 'N2P:';
    public const BADGE_PREFIX = 'N2P-B:';
    public const SEPARATOR = '*';

    /** Caractères interdits dans un champ : séparateurs « * » (écrit) et « | » (lu). */
    private const RESERVED = ['*', '|'];

    /**
     * Code d'une opération de ligne de commande, ou null si elle n'en a pas :
     * tâche hors ligne de commande, ou code opération contenant un séparateur.
     */
    public static function forTask(Task $task): ?string
    {
        if (! $task->order_lines_id) {
            return null;
        }

        $operationCode = N2PPayloadBuilder::operationCode($task);
        if (! self::isEncodable($operationCode)) {
            return null;
        }

        $seq = (int) $task->ordre >= 1 ? (string) (int) $task->ordre : '';

        return self::OPERATION_PREFIX . implode(self::SEPARATOR, [
            'OF' . $task->order_lines_id,
            (string) $task->order_lines_id,
            $operationCode,
            $seq,
        ]);
    }

    /**
     * Champs d'un code d'opération lu (séparateur « * », « | » accepté), ou null si
     * ce n'en est pas un. seq vide = null (première opération de ce code).
     *
     * @return array{of_code: string, line_ref: string, operation_code: string, seq: ?int}|null
     */
    public static function parse(string $raw): ?array
    {
        $value = trim($raw);
        if (stripos($value, self::OPERATION_PREFIX) !== 0) {
            return null;
        }

        $parts = array_map('trim', preg_split('/[*|]/', substr($value, strlen(self::OPERATION_PREFIX))));
        if (count($parts) !== 4) {
            return null;
        }

        [$ofCode, $lineRef, $operationCode, $seq] = $parts;
        if (($ofCode === '' && $lineRef === '') || $operationCode === '' || ($seq !== '' && ! ctype_digit($seq))) {
            return null;
        }

        return [
            // Douchette en majuscules bloquées : « of2338 » vaut « OF2338 ».
            'of_code' => strtoupper($ofCode),
            'line_ref' => $lineRef,
            'operation_code' => $operationCode,
            'seq' => $seq !== '' ? (int) $seq : null,
        ];
    }

    /** Code imprimé sur le badge d'un opérateur. */
    public static function forBadge(string $badgeCode): string
    {
        return self::BADGE_PREFIX . self::normalizeBadge($badgeCode);
    }

    /**
     * Code badge lu (N2P-B:{code}), normalisé, ou null si ce n'est pas un badge.
     * Testé avant les opérations : « N2P-B: » commence aussi par « N2P ».
     */
    public static function parseBadge(string $raw): ?string
    {
        $value = trim($raw);
        if (stripos($value, self::BADGE_PREFIX) !== 0) {
            return null;
        }

        $badge = self::normalizeBadge(substr($value, strlen(self::BADGE_PREFIX)));

        return self::isEncodable($badge) ? $badge : null;
    }

    /** Majuscules : une douchette en majuscules bloquées lit le même badge. */
    public static function normalizeBadge(string $badgeCode): string
    {
        return strtoupper(trim($badgeCode));
    }

    /**
     * Tâche désignée par une saisie de douchette : code N2P:… ou ancien n° de tâche seul.
     */
    public static function resolveTask(string $raw): ?Task
    {
        $value = trim($raw);
        if ($value !== '' && ctype_digit($value)) {
            return Task::query()->find((int) $value);
        }

        $fields = self::parse($value);

        return $fields === null ? null : Task::resolveByExternalRef(
            $fields['of_code'],
            $fields['line_ref'],
            $fields['operation_code'],
            $fields['seq'],
        );
    }

    public static function isEncodable(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        foreach (self::RESERVED as $char) {
            if (str_contains($value, $char)) {
                return false;
            }
        }

        return true;
    }
}
