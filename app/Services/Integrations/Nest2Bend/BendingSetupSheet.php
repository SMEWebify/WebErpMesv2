<?php

namespace App\Services\Integrations\Nest2Bend;

use App\Models\File;
use App\Models\User;
use App\Models\Workflow\BendingSimulation;
use App\Models\Workflow\QuoteLines;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use App\Services\Files\FileVersioning;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fiche de réglage PDF d'une simulation NEST2BEND → GED de la ligne de devis.
 *
 * Le PDF est lu côté serveur avec la clé API (jamais depuis le navigateur), à l'URL `fiche`
 * du résultat. S'il existe déjà une fiche NEST2BEND sur la ligne, le nouveau PDF en devient
 * une nouvelle version ; sinon il est rangé et rattaché à la ligne.
 *
 * Un échec (404, 503, délai dépassé) ne remonte jamais : le temps est déjà enregistré. La
 * simulation passe en `failed` avec le seul statut HTTP, et « Récupérer la fiche » retente
 * (le résultat reste lisible 30 jours chez NEST2BEND).
 */
class BendingSetupSheet
{
    public const HASHTAG = 'nest2bend';

    public function __construct(
        private readonly Nest2BendAccess $access,
        private readonly Nest2BendGatewayFactory $factory,
        private readonly FileStorageService $files,
        private readonly FileVersioning $versioning,
    ) {}

    /**
     * Télécharge et range la fiche ; ne lève pas d'exception.
     */
    public function fetch(BendingSimulation $simulation, QuoteLines $line, ?User $user = null): BendingSimulation
    {
        if ($simulation->fiche_url === null || $simulation->fiche_status === BendingSimulation::SHEET_STORED) {
            return $simulation;
        }

        // Deux clics « Récupérer » simultanés ne doivent pas créer deux versions.
        $lock = Cache::lock('nest2bend-sheet-line-' . $line->id, 120);
        if (! $lock->get()) {
            return $simulation;
        }

        try {
            $simulation->refresh();
            if ($simulation->fiche_status === BendingSimulation::SHEET_STORED) {
                return $simulation;
            }

            $setting = $this->access->setting();
            if (! $setting?->hasKey()) {
                throw new Nest2BendException(Nest2BendException::INACTIVE, __('nest2bend.errors.inactive'), 403);
            }

            $sheet = $this->factory->make($setting)->setupSheet($simulation->fiche_url);
            $file = $this->store($line, $sheet->contents, $sheet->fileName, $user);

            $simulation->forceFill([
                'fiche_status'  => BendingSimulation::SHEET_STORED,
                'fiche_file_id' => $file->id,
                'fiche_error'   => null,
            ])->save();
        } catch (Nest2BendException $e) {
            $this->failed($simulation, $e->status ?? 0);
        } catch (\Throwable $e) {
            // Disque plein, GED… : le statut seul, jamais le contenu.
            Log::warning('NEST2BEND : fiche de réglage non rangée', ['simulation' => $simulation->id, 'exception' => $e::class]);
            $this->failed($simulation, 0);
        } finally {
            $lock->release();
        }

        return $simulation;
    }

    /**
     * Fiche NEST2BEND déjà rangée sur la ligne et toujours rattachée : sa version courante.
     */
    public function current(QuoteLines $line): ?File
    {
        $ids = BendingSimulation::where('quote_lines_id', $line->id)
            ->whereNotNull('fiche_file_id')
            ->orderByDesc('id')
            ->pluck('fiche_file_id');

        foreach ($ids as $id) {
            $file = File::find($id);
            if ($file === null) {
                continue;
            }

            $lineage = $file->lineageId();
            $latest = File::where(fn ($q) => $q->whereKey($lineage)->orWhere('version_group_id', $lineage))
                ->whereNull('superseded_at')
                ->orderByDesc('id')
                ->first();

            if ($latest !== null && $line->files()->whereKey($latest->id)->exists()) {
                return $latest;
            }
        }

        return null;
    }

    private function store(QuoteLines $line, string $contents, string $name, ?User $user): File
    {
        $comment = __('nest2bend.sheet.comment');
        $previous = $this->current($line);

        if ($previous !== null) {
            return $this->versioning->replaceWithContents($previous, $contents, $name, 'application/pdf', ['comment' => $comment], $user);
        }

        $file = $this->files->storeContents($contents, $name, 'application/pdf', [
            'comment'  => $comment,
            'hashtags' => [self::HASHTAG],
        ]);
        $this->files->attach($file, $line, FileRole::OTHER);

        return $file;
    }

    private function failed(BendingSimulation $simulation, int $status): void
    {
        Log::warning('NEST2BEND : fiche de réglage non récupérée', ['simulation' => $simulation->id, 'status' => $status]);

        $simulation->forceFill([
            'fiche_status' => BendingSimulation::SHEET_FAILED,
            'fiche_error'  => $status,
        ])->save();
    }
}
