<?php

namespace App\Jobs;

use App\Services\AI\AIGateway;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAIRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly AIRequest $request,
        private readonly string    $providerName,
        /**
         * Classe de callback à appeler avec le résultat.
         * Doit implémenter la méthode statique : handle(AIResponse $response, AIRequest $request): void
         *
         * @var class-string
         */
        private readonly string    $callbackClass,
    ) {}

    public function handle(AIGateway $gateway): void
    {
        $response = $gateway->complete($this->request, $this->providerName);

        if (! class_exists($this->callbackClass)) {
            Log::error("[AI:Job] Callback class introuvable : {$this->callbackClass}");
            return;
        }

        if (! method_exists($this->callbackClass, 'handle')) {
            Log::error("[AI:Job] Méthode handle() manquante sur : {$this->callbackClass}");
            return;
        }

        ($this->callbackClass)::handle($response, $this->request);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[AI:Job] Echec du job', [
            'provider' => $this->providerName,
            'callback' => $this->callbackClass,
            'module'   => $this->request->metadata['module'] ?? 'unknown',
            'error'    => $exception->getMessage(),
        ]);
    }
}
