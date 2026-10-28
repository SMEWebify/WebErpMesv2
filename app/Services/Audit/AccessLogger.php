<?php

namespace App\Services\Audit;

use App\Models\File;
use App\Models\Products\Batch;
use App\Models\User;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Journal des consultations des fiches sensibles, dans activity_log (log_name `access`).
 *
 * Une seule écriture par consultation retenue : le sujet n'est jamais chargé (la clé
 * suffit), et une consultation répétée du même sujet par le même utilisateur dans la
 * fenêtre de config('audit.dedup_minutes') est ignorée.
 */
class AccessLogger
{
    /**
     * Sujets traçables : alias de route → modèle, et colonne affichée à l'écran.
     */
    public const SUBJECTS = [
        'opportunity' => ['model' => Opportunities::class, 'label' => 'label'],
        'quote'       => ['model' => Quotes::class,        'label' => 'code'],
        'order'       => ['model' => Orders::class,        'label' => 'code'],
        'order-line'  => ['model' => OrderLines::class,    'label' => 'label'],
        'file'        => ['model' => File::class,          'label' => 'original_file_name'],
        'batch'       => ['model' => Batch::class,         'label' => 'code'],
    ];

    public static function enabled(): bool
    {
        return (bool) config('audit.log_reads');
    }

    public static function logName(): string
    {
        return (string) config('audit.log_name', 'access');
    }

    /**
     * Enregistre la consultation. Renvoie vrai si une ligne a été écrite.
     */
    public function record(User $user, string $type, Model|int|string $subject, ?string $route, ?string $ip): bool
    {
        if (! self::enabled() || ! isset(self::SUBJECTS[$type])) {
            return false;
        }

        $model = $subject instanceof Model ? $subject : $this->reference($type, $subject);
        if ($model === null) {
            return false;
        }

        $minutes = max(1, (int) config('audit.dedup_minutes', 10));
        $key = sprintf('audit-access:%d:%s:%s', $user->getKey(), $model->getMorphClass(), $model->getKey());

        // add() n'écrit que si la clé est absente : deux requêtes simultanées ne
        // produisent qu'une ligne.
        if (! Cache::add($key, true, now()->addMinutes($minutes))) {
            return false;
        }

        activity(self::logName())
            ->causedBy($user)
            ->performedOn($model)
            ->event('read')
            ->withProperties(array_filter([
                'route' => $route,
                'ip'    => $ip,
            ], fn ($value) => $value !== null))
            ->log('read');

        return true;
    }

    /**
     * Instance non chargée portant seulement la clé : performedOn() n'a besoin que du
     * type et de l'identifiant, inutile d'interroger la base.
     */
    private function reference(string $type, int|string $id): ?Model
    {
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        $class = self::SUBJECTS[$type]['model'];
        $model = new $class();
        $model->setAttribute($model->getKeyName(), (int) $id);
        $model->exists = true;

        return $model;
    }

    /**
     * Alias d'un sujet à partir de sa classe (pour l'écran de consultation).
     */
    public static function typeOf(?string $class): ?string
    {
        foreach (self::SUBJECTS as $type => $config) {
            if ($config['model'] === $class) {
                return $type;
            }
        }

        return null;
    }
}
