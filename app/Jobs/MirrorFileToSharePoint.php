<?php

namespace App\Jobs;

use App\Models\Integrations\SharePointMirrorItem;
use App\Services\Integrations\SharePoint\SharePointException;
use App\Services\Integrations\SharePoint\SharePointMirror;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recopie d'une lignée de fichier de la GED dans SharePoint.
 *
 * Le job ne porte que l'id de la ligne `sharepoint_mirror_items` : il relit au moment de
 * s'exécuter la dernière version demandée, donc deux remplacements rapprochés ne font
 * qu'un envoi utile. Unique jusqu'au démarrage : une demande arrivée pendant l'envoi
 * repart après lui.
 */
class MirrorFileToSharePoint implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 6;

    public $backoff = [60, 300, 900, 1800, 3600];

    public $uniqueFor = 3600;

    public function __construct(public readonly int $itemId) {}

    public function uniqueId(): string
    {
        return (string) $this->itemId;
    }

    public function handle(SharePointMirror $mirror): void
    {
        $item = SharePointMirrorItem::find($this->itemId);
        if (! $item) {
            return;
        }

        $lock = Cache::lock('sharepoint:item:' . $item->id, 600);
        if (! $lock->get()) {
            $this->release(30);

            return;
        }

        try {
            $item->increment('attempts');
            $mirror->sync($item);
        } catch (SharePointException $e) {
            $item->forceFill(['last_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            // Réseau, limitation, panne Microsoft : on réessaie. Droits, site, fichier
            // illisible : inutile, l'écran propose « Relancer » une fois corrigé.
            if ($e->isTransient() && $this->attempts() < $this->tries) {
                $this->release($e->retryAfter ?? $this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);

                return;
            }

            $this->markFailed($item, $e);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($item = SharePointMirrorItem::find($this->itemId)) {
            $this->markFailed($item, $exception);
        }
    }

    private function markFailed(SharePointMirrorItem $item, Throwable $e): void
    {
        $item->forceFill([
            'status' => SharePointMirrorItem::STATUS_FAILED,
            'last_error' => mb_substr($e->getMessage(), 0, 500),
        ])->save();

        Log::warning('SharePoint: recopie en échec', [
            'item_id' => $item->id,
            'file_id' => $item->file_id,
            'error' => $e->getMessage(),
        ]);
    }
}
