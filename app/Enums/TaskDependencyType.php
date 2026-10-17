<?php

namespace App\Enums;

/**
 * Type d'un lien entre deux opérations d'une gamme (notation MS Project).
 *
 * Le lien porte sur le successeur : FS = il démarre quand le prédécesseur
 * finit (le cas par défaut, celui de l'enchaînement par `ordre`), SS = il
 * démarre quand le prédécesseur démarre, FF = il finit quand le prédécesseur
 * finit. Le début-à-fin (SF) n'a pas d'usage en atelier.
 */
enum TaskDependencyType: string
{
    case FinishToStart  = 'FS';
    case StartToStart   = 'SS';
    case FinishToFinish = 'FF';

    /** Code court affiché dans la gamme : FD / DD / FF en français. */
    public function shortLabel(): string
    {
        return __('planning.dependency_short.' . $this->value);
    }

    /** Un décalage en pièces n'a de sens que si les deux opérations se chevauchent. */
    public function acceptsPieceLag(): bool
    {
        return $this !== self::FinishToStart;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
