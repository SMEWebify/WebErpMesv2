<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AISettingsResolver;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    public function __construct(private readonly AISettingsResolver $settings) {}

    public function getName(): string
    {
        return 'claude';
    }

    public function complete(AIRequest $request): AIResponse
    {
        $config = $this->settings->claude();
        $apiKey = $config['api_key'];

        if (empty($apiKey)) {
            return AIResponse::failure('Clé API Claude non configurée. Renseignez-la dans /admin/integrations/ai.', $this->getName());
        }

        $model     = $request->model     ?? $config['model'];
        $maxTokens = $request->maxTokens ?? $config['max_tokens'];
        $timeout   = $config['timeout'];

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
                'anthropic-version' => $this->settings->claudeApiVersion(),
                'content-type'      => 'application/json',
            ])
            ->timeout($timeout)
            ->post($this->settings->claudeEndpoint(), $payload);

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
