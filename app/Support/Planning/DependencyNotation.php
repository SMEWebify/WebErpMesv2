<?php

namespace App\Support\Planning;

use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;
use App\Services\Planning\DependencyEdge;
use InvalidArgumentException;

/**
 * Notation courte des liens d'une opération, à la MS Project : « 20FD;30FD »,
 * « 20DD+2h », « 20DD+10pc;20FF+10pc ». Les opérations sont désignées par leur
 * numéro dans la gamme (`ordre`).
 *
 * Lecture : FD/DD/FF (français) et FS/SS/FF (anglais) sont acceptés ; sans
 * type, c'est un fin-début ; sans unité, des heures. Séparateur « ; » (la
 * virgule reste la marque décimale : « 30DD+1,5h »).
 */
final class DependencyNotation
{
    private const TYPES = [
        'FD' => TaskDependencyType::FinishToStart,
        'FS' => TaskDependencyType::FinishToStart,
        'DD' => TaskDependencyType::StartToStart,
        'SS' => TaskDependencyType::StartToStart,
        'FF' => TaskDependencyType::FinishToFinish,
    ];

    private const UNITS = [
        ''       => TaskDependencyLagUnit::Hour,
        'h'      => TaskDependencyLagUnit::Hour,
        'pc'     => TaskDependencyLagUnit::Piece,
        'pcs'    => TaskDependencyLagUnit::Piece,
        'p'      => TaskDependencyLagUnit::Piece,
        'piece'  => TaskDependencyLagUnit::Piece,
        'pieces' => TaskDependencyLagUnit::Piece,
        'pièce'  => TaskDependencyLagUnit::Piece,
        'pièces' => TaskDependencyLagUnit::Piece,
    ];

    /**
     * @param  array<int|string, int>  $taskIdByOrdre  numéro d'opération → id de tâche
     * @return array<int, array{predecessor_task_id: int, type: TaskDependencyType, lag_value: float, lag_unit: TaskDependencyLagUnit}>
     *
     * @throws InvalidArgumentException  élément illisible ou opération inconnue
     */
    public static function parse(string $notation, array $taskIdByOrdre): array
    {
        $links = [];

        foreach (explode(';', $notation) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            if (! preg_match('/^(\d+)\s*([A-Za-z]{2})?\s*(?:\+\s*(\d+(?:[.,]\d+)?)\s*([\p{L}]*))?$/u', $item, $m)) {
                throw new InvalidArgumentException(__('planning.notation_unreadable', ['item' => $item]));
            }

            $ordre = $m[1];
            if (! isset($taskIdByOrdre[$ordre])) {
                throw new InvalidArgumentException(__('planning.notation_unknown_operation', ['ordre' => $ordre]));
            }

            $code = strtoupper($m[2] ?? '');
            $type = $code === '' ? TaskDependencyType::FinishToStart : (self::TYPES[$code] ?? null);
            if ($type === null) {
                throw new InvalidArgumentException(__('planning.notation_unknown_type', ['item' => $item]));
            }

            $unitCode = mb_strtolower($m[4] ?? '');
            $unit = self::UNITS[$unitCode] ?? null;
            if ($unit === null) {
                throw new InvalidArgumentException(__('planning.notation_unknown_unit', ['item' => $item]));
            }

            $links[] = [
                'predecessor_task_id' => (int) $taskIdByOrdre[$ordre],
                'type'                => $type,
                'lag_value'           => isset($m[3]) && $m[3] !== '' ? (float) str_replace(',', '.', $m[3]) : 0.0,
                'lag_unit'            => $unit,
            ];
        }

        return $links;
    }

    /**
     * @param  DependencyEdge[]  $edges  liens entrants d'une opération
     * @param  array<int, int|string>  $ordreByTaskId  id de tâche → numéro d'opération
     * @param  bool  $canonical  codes FS/SS/FF quelle que soit la langue (API, export)
     */
    public static function format(array $edges, array $ordreByTaskId, bool $canonical = false): string
    {
        $items = [];

        foreach ($edges as $edge) {
            $code = $canonical ? $edge->type->value : $edge->type->shortLabel();
            $item = ($ordreByTaskId[$edge->from] ?? '#' . $edge->from) . $code;

            if ($edge->lagValue > 0) {
                $value = rtrim(rtrim(number_format($edge->lagValue, 3, '.', ''), '0'), '.');
                $item .= '+' . $value . ($edge->lagUnit === TaskDependencyLagUnit::Piece ? 'pc' : 'h');
            }

            $items[] = $item;
        }

        return implode(';', $items);
    }

    /**
     * Liens saisis (TaskDependency, relation `predecessor` chargée) en notation
     * courte : « 20FS;30SS+2h » pour l'API et les exports.
     *
     * @param  iterable<\App\Models\Planning\TaskDependency>  $links
     */
    public static function formatLinks(iterable $links, bool $canonical = true): string
    {
        $edges = [];
        $ordre = [];

        foreach ($links as $link) {
            $edges[] = new DependencyEdge(
                (int) $link->predecessor_task_id,
                (int) $link->task_id,
                $link->type,
                (float) $link->lag_value,
                $link->lag_unit,
            );
            $ordre[(int) $link->predecessor_task_id] = $link->predecessor?->ordre;
        }

        return self::format($edges, $ordre, $canonical);
    }
}
