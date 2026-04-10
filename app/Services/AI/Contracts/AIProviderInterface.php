<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;

interface AIProviderInterface
{
    /**
     * Nom du provider (doit correspondre à la clé dans config('ai.providers')).
     */
    public function getName(): string;

    /**
     * Envoie une requête et retourne une réponse.
     */
    public function complete(AIRequest $request): AIResponse;
}
