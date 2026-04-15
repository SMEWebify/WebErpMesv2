<?php

namespace App\Services\AI\DTOs;

class AIRequest
{
    /**
     * @param  string       $prompt        Prompt utilisateur final
     * @param  array        $context       Données métier injectées dans le prompt
     * @param  string|null  $systemPrompt  System prompt du module (override)
     * @param  string|null  $model         Modèle à utiliser (override du default config)
     * @param  int|null     $maxTokens     Limite de tokens en sortie
     * @param  array        $messages      Historique multi-tour [['role'=>'user','content'=>'...']]
     * @param  array        $metadata      Données libres (logging, tracing, etc.)
     * @param  array        $tools         Définitions d'outils Claude (tool use)
     */
    public function __construct(
        public readonly string  $prompt,
        public readonly array   $context       = [],
        public readonly ?string $systemPrompt  = null,
        public readonly ?string $model         = null,
        public readonly ?int    $maxTokens     = null,
        public readonly array   $messages      = [],
        public readonly array   $metadata      = [],
        public readonly array   $tools         = [],
    ) {}

    public static function make(string $prompt, array $context = []): self
    {
        return new self($prompt, $context);
    }

    public function withSystemPrompt(string $systemPrompt): self
    {
        return new self(
            $this->prompt,
            $this->context,
            $systemPrompt,
            $this->model,
            $this->maxTokens,
            $this->messages,
            $this->metadata,
            $this->tools,
        );
    }

    public function withModel(string $model): self
    {
        return new self(
            $this->prompt,
            $this->context,
            $this->systemPrompt,
            $model,
            $this->maxTokens,
            $this->messages,
            $this->metadata,
            $this->tools,
        );
    }

    public function withMaxTokens(int $maxTokens): self
    {
        return new self(
            $this->prompt,
            $this->context,
            $this->systemPrompt,
            $this->model,
            $maxTokens,
            $this->messages,
            $this->metadata,
            $this->tools,
        );
    }

    public function withMeta(array $metadata): self
    {
        return new self(
            $this->prompt,
            $this->context,
            $this->systemPrompt,
            $this->model,
            $this->maxTokens,
            $this->messages,
            array_merge($this->metadata, $metadata),
            $this->tools,
        );
    }

    public function withTools(array $tools): self
    {
        return new self(
            $this->prompt,
            $this->context,
            $this->systemPrompt,
            $this->model,
            $this->maxTokens,
            $this->messages,
            $this->metadata,
            $tools,
        );
    }

    public function withMessages(array $messages): self
    {
        return new self(
            $this->prompt,
            $this->context,
            $this->systemPrompt,
            $this->model,
            $this->maxTokens,
            $messages,
            $this->metadata,
            $this->tools,
        );
    }

    /**
     * Construit les messages pour l'API Claude :
     * historique + message utilisateur courant.
     */
    public function buildMessages(): array
    {
        $history = $this->messages;
        $history[] = ['role' => 'user', 'content' => $this->prompt];
        return $history;
    }
}
