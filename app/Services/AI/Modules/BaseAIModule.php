<?php

namespace App\Services\AI\Modules;

use App\Services\AI\AIGateway;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;

abstract class BaseAIModule
{
    public function __construct(
        protected readonly AIGateway $gateway,
    ) {}

    /**
     * System prompt qui définit le rôle et le contexte métier du module.
     */
    abstract protected function systemPrompt(): string;

    /**
     * Provider à utiliser. null = default config.
     * Surcharger dans le module pour forcer 'python_ml' ou un modèle spécifique.
     */
    protected function provider(): ?string
    {
        return null;
    }

    /**
     * Modèle à utiliser. null = default du provider.
     */
    protected function model(): ?string
    {
        return null;
    }

    /**
     * Appel synchrone.
     *
     * @param  string  $prompt   Question ou instruction en langage naturel
     * @param  array   $context  Données métier (ex: ['nc' => $nc->toArray()])
     */
    public function ask(string $prompt, array $context = []): AIResponse
    {
        $request = $this->buildRequest($prompt, $context);
        return $this->gateway->complete($request, $this->provider());
    }

    /**
     * Appel asynchrone : le résultat sera traité par $callbackClass::handle(AIResponse, AIRequest).
     *
     * @param  class-string  $callbackClass
     */
    public function dispatch(string $prompt, string $callbackClass, array $context = []): void
    {
        $request = $this->buildRequest($prompt, $context);
        $this->gateway->dispatch($request, $callbackClass, $this->provider());
    }

    private function buildRequest(string $prompt, array $context): AIRequest
    {
        $request = AIRequest::make($prompt, $context)
            ->withSystemPrompt($this->systemPrompt())
            ->withMeta(['module' => static::class]);

        if ($this->model() !== null) {
            $request = $request->withModel($this->model());
        }

        return $request;
    }
}
