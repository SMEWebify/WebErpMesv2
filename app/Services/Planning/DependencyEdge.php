<?php

namespace App\Services\Planning;

use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;

/**
 * Lien effectif entre deux opérations : saisi (table task_dependencies) ou
 * implicite (fin-début sur l'opération précédente de la gamme).
 */
final class DependencyEdge
{
    public function __construct(
        public readonly int $from,
        public readonly int $to,
        public readonly TaskDependencyType $type = TaskDependencyType::FinishToStart,
        public readonly float $lagValue = 0.0,
        public readonly TaskDependencyLagUnit $lagUnit = TaskDependencyLagUnit::Hour,
        public readonly bool $implicit = false,
    ) {}
}
