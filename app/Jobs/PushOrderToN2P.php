<?php

namespace App\Jobs;

use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use App\Services\N2P\N2PClient;
use App\Services\N2P\N2PPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PushOrderToN2P implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 900, 1800, 3600];

    public function __construct(public readonly int $orderId, public readonly bool $skipTasks = false)
    {
    }

    public function handle(N2PPayloadBuilder $payloadBuilder): void
    {
        $endpoint = IntegrationEndpoint::query()
            ->forSystem('n2p')
            ->outbound()
            ->active()
            ->first();

        if (! $endpoint) {
            throw new RuntimeException('N2P outbound endpoint not configured or disabled');
        }

        $order = Orders::with([
                'OrderLines.OrderLineDetails',
                'OrderLines.Product',
                'OrderLines.Product.SubAssembly',
                'OrderLines.SubAssembly',
                'OrderLines.Task',
                'OrderLines.files',
                'OrderLines.Product.files',
                'companie',
            ])
            ->findOrFail($this->orderId);

        // Options de payload lues sur l'endpoint (metadata JSON), plus sur
        // la table settings — mono-source-of-truth par endpoint.
        // N2PPayloadBuilder attend encore les clés préfixées n2p_* (contrat
        // non touché ici pour ne pas casser les tests unit du builder).
        //
        // skipTasks force l'omission du bloc tasks du payload — utilisé par
        // le repush "livraison" (OrdersObserver) : N2P efface et recrée toutes
        // les tâches à chaque envoi (JobSyncService::sync), un repush avec
        // tasks détruirait l'historique atelier (actual_time_min, statuts).
        // Le repush de livraison ne modifie QUE le statut du job.
        $businessConfig = [
            'n2p_job_status_on_send' => $endpoint->meta(IntegrationEndpoint::META_JOB_STATUS_ON_SEND),
            'n2p_priority_default'   => $endpoint->meta(IntegrationEndpoint::META_DEFAULT_PRIORITY),
            'n2p_send_tasks'         => $this->skipTasks
                ? false
                : $endpoint->meta(IntegrationEndpoint::META_SEND_TASKS),
        ];

        $payload = $payloadBuilder->build($order, $businessConfig);

        $client = new N2PClient($endpoint);
        $response = $client->pushJobs($payload);

        $order->update([
            'n2p_last_push_at' => now(),
            'n2p_last_push_status' => 'OK',
            'n2p_last_push_error' => null,
        ]);

        Log::channel('n2p')->info('N2P push success', [
            'order_id' => $order->getKey(),
            'endpoint_id' => $endpoint->id,
            'response' => $response,
        ]);

        // Pas de repush documents sur la transition livraison : le SVG n'a pas
        // changé, N2P l'a déjà. Ça évite un aller-retour HTTP par ligne à
        // chaque BL et un risque d'ID job N2P désynchronisé.
        if (! $this->skipTasks) {
            $this->pushPrimarySvgs($client, $order, $response);
        }
    }

    /**
     * Attache le SVG primaire (role=vectoriel) de chaque ligne à son OF côté N2P
     * via /api/erp/jobs/{id}/documents. Résolution : d'abord le fichier attaché
     * à l'orderLine, sinon celui du produit — c'est la même stratégie que
     * l'écran show pour choisir quel dessin exposer.
     *
     * Un échec par ligne n'interrompt pas la boucle : le job N2P a été créé,
     * le SVG manquant peut être renvoyé plus tard (renvoi manuel de commande).
     * On ne relance pas le push OF pour ça.
     */
    private function pushPrimarySvgs(N2PClient $client, Orders $order, array $pushJobsResponse): void
    {
        $jobIdByOfCode = $this->buildOfCodeToJobIdMap($pushJobsResponse);

        if ($jobIdByOfCode === []) {
            Log::channel('n2p')->warning('N2P push jobs response contains no of_code/id mapping — skipping SVG uploads', [
                'order_id' => $order->getKey(),
            ]);
            return;
        }

        foreach ($order->OrderLines as $orderLine) {
            $ofCode = 'OF' . $orderLine->id;
            $jobId = $jobIdByOfCode[$ofCode] ?? null;

            if ($jobId === null) {
                continue;
            }

            $file = $this->resolvePrimaryVectorFile($orderLine);
            if ($file === null) {
                continue;
            }

            $located = app(FileStorageService::class)->locate($file);
            if ($located === null) {
                Log::channel('n2p')->warning('N2P SVG upload skipped — file not on disk', [
                    'order_line_id' => $orderLine->id,
                    'file_id' => $file->id,
                ]);
                continue;
            }

            [$disk, $path] = $located;

            try {
                $body = Storage::disk($disk)->get($path);
            } catch (Throwable $e) {
                Log::channel('n2p')->warning('N2P SVG upload skipped — read error', [
                    'order_line_id' => $orderLine->id,
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if ($body === null || $body === '') {
                continue;
            }

            $filename = $file->original_file_name ?: $file->name ?: 'part.svg';

            try {
                $client->pushJobDocument($jobId, $body, 'image/svg+xml', $filename);
            } catch (Throwable $e) {
                Log::channel('n2p')->warning('N2P SVG upload failed', [
                    'order_line_id' => $orderLine->id,
                    'job_id' => $jobId,
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param array<string,mixed> $pushJobsResponse
     * @return array<string,int>
     */
    private function buildOfCodeToJobIdMap(array $pushJobsResponse): array
    {
        $map = [];
        foreach ((array) ($pushJobsResponse['jobs'] ?? []) as $job) {
            $ofCode = (string) ($job['of_code'] ?? '');
            $id = (int) ($job['id'] ?? 0);
            if ($ofCode !== '' && $id > 0) {
                $map[$ofCode] = $id;
            }
        }
        return $map;
    }

    /**
     * Fichier vectoriel primaire (SVG) attaché à la ligne, avec fallback sur le
     * produit — même règle de résolution que l'écran show. Retourne null si
     * aucun SVG primaire n'est trouvé (cas normal pour pièces standard).
     */
    private function resolvePrimaryVectorFile(OrderLines $orderLine)
    {
        $file = $orderLine->files
            ->first(fn ($f) => $f->pivot->role === FileRole::VECTOR && (bool) $f->pivot->is_primary);

        if ($file) {
            return $file;
        }

        $product = $orderLine->Product;
        if (! $product) {
            return null;
        }

        return $product->files
            ->first(fn ($f) => $f->pivot->role === FileRole::VECTOR && (bool) $f->pivot->is_primary);
    }

    public function failed(Throwable $exception): void
    {
        Orders::query()
            ->whereKey($this->orderId)
            ->update([
                'n2p_last_push_at' => now(),
                'n2p_last_push_status' => 'ERROR',
                'n2p_last_push_error' => $exception->getMessage(),
            ]);

        Log::channel('n2p')->error('N2P push failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
