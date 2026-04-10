<?php

namespace App\Services\AI\DTOs;

class AIResponse
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $content,
        public readonly string  $provider,
        public readonly string  $model,
        public readonly int     $inputTokens  = 0,
        public readonly int     $outputTokens = 0,
        public readonly ?string $error        = null,
    ) {}

    public static function success(
        string $content,
        string $provider,
        string $model,
        int    $inputTokens  = 0,
        int    $outputTokens = 0,
    ): self {
        return new self(
            success:      true,
            content:      $content,
            provider:     $provider,
            model:        $model,
            inputTokens:  $inputTokens,
            outputTokens: $outputTokens,
        );
    }

    public static function failure(string $error, string $provider, string $model = ''): self
    {
        return new self(
            success:  false,
            content:  '',
            provider: $provider,
            model:    $model,
            error:    $error,
        );
    }

    public function failed(): bool
    {
        return ! $this->success;
    }

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    public function toArray(): array
    {
        return [
            'success'       => $this->success,
            'content'       => $this->content,
            'provider'      => $this->provider,
            'model'         => $this->model,
            'input_tokens'  => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'error'         => $this->error,
        ];
    }
}
