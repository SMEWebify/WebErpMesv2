<?php

namespace App\Services\AI;

use App\Jobs\ProcessAIRequestJob;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\Modules\BaseAIModule;
use InvalidArgumentException;

class AIGateway
{
    /** @var array<string, AIProviderInterface> */
    private array $providers = [];

    public function registerProvider(AIProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Appel synchrone.
     * Utilise le provider passé en argument, sinon celui du module, sinon le default config.
     */
    public function complete(AIRequest $request, ?string $providerName = null): AIResponse
    {
        $provider = $this->resolveProvider($providerName);
        return $provider->complete($request);
    }

    /**
     * Appel asynchrone (via queue).
     *
     * @param  class-string<\App\Jobs\ProcessAIRequestJob>  $callbackClass  Classe dont la méthode
     *         statique handle(AIResponse, AIRequest) sera appelée dans le job.
     */
    public function dispatch(AIRequest $request, string $callbackClass, ?string $providerName = null): void
    {
        $providerName ??= config('ai.default_provider', 'claude');

        ProcessAIRequestJob::dispatch($request, $providerName, $callbackClass)
            ->onQueue(config('ai.queue', 'default'));
    }

    /**
     * Résout et retourne un module AI.
     *
     * Usage : app(AIGateway::class)->module(QuoteAI::class)->ask('...');
     *
     * @template T of BaseAIModule
     * @param  class-string<T>  $moduleClass
     * @return T
     */
    public function module(string $moduleClass): BaseAIModule
    {
        if (! is_subclass_of($moduleClass, BaseAIModule::class)) {
            throw new InvalidArgumentException("{$moduleClass} must extend BaseAIModule.");
        }

        return app($moduleClass);
    }

    private function resolveProvider(?string $name): AIProviderInterface
    {
        $name ??= config('ai.default_provider', 'claude');

        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException("Provider AI inconnu : '{$name}'. Providers disponibles : " . implode(', ', array_keys($this->providers)));
        }

        return $this->providers[$name];
    }

    /** @return array<string> */
    public function availableProviders(): array
    {
        return array_keys($this->providers);
    }
}
