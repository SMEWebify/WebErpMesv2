<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    private readonly array $config;

    public function __construct()
    {
        $this->config = config('ai.providers.claude', []);
    }

    public function getName(): string
    {
        return 'claude';
    }

    public function complete(AIRequest $request): AIResponse
    {
        $apiKey = $this->config['api_key'] ?? null;

        if (empty($apiKey)) {
            return AIResponse::failure('ANTHROPIC_API_KEY non configurée.', $this->getName());
        }

        $model     = $request->model     ?? $this->config['default_model'];
        $maxTokens = $request->maxTokens ?? $this->config['max_tokens'] ?? 1024;
        $timeout   = $this->config['timeout'] ?? 30;

        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $request->buildMessages(),
        ];

        if ($request->systemPrompt !== null) {
            $payload['system'] = $request->systemPrompt;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => $this->config['api_version'] ?? '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout($timeout)
            ->post($this->config['api_url'] ?? 'https://api.anthropic.com/v1/messages', $payload);

            if ($response->failed()) {
                $errorBody = $response->json('error.message', $response->body());
                $this->log('error', $request, "HTTP {$response->status()}: {$errorBody}");
                return AIResponse::failure("Erreur API Claude : {$errorBody}", $this->getName(), $model);
            }

            $data    = $response->json();
            $content = $data['content'][0]['text'] ?? '';
            $usage   = $data['usage'] ?? [];

            $this->log('info', $request, $content);

            return AIResponse::success(
                content:      $content,
                provider:     $this->getName(),
                model:        $data['model'] ?? $model,
                inputTokens:  $usage['input_tokens']  ?? 0,
                outputTokens: $usage['output_tokens'] ?? 0,
            );

        } catch (\Throwable $e) {
            $this->log('error', $request, $e->getMessage());
            return AIResponse::failure($e->getMessage(), $this->getName(), $model);
        }
    }

    private function log(string $level, AIRequest $request, string $detail): void
    {
        if (! config('ai.logging', false)) {
            return;
        }

        Log::channel('daily')->$level('[AI:Claude] ' . $detail, [
            'module'   => $request->metadata['module'] ?? 'unknown',
            'prompt'   => mb_substr($request->prompt, 0, 200),
        ]);
    }
}
