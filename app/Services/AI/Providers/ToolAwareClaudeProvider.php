<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AISettingsResolver;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\Tools\ERPToolRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider Claude avec support natif du tool use (function calling).
 *
 * Gère la boucle agentique :
 *   1. Envoie le message + définitions d'outils à Claude
 *   2. Si Claude répond tool_use → exécute l'outil via ERPToolRegistry
 *   3. Renvoie le résultat à Claude comme tool_result
 *   4. Répète jusqu'à stop_reason = end_turn (max 10 tours)
 */
class ToolAwareClaudeProvider implements AIProviderInterface
{
    private const MAX_TOOL_ROUNDS = 10;

    public function __construct(
        private readonly ERPToolRegistry   $toolRegistry,
        private readonly AISettingsResolver $settings,
    ) {}

    public function getName(): string
    {
        return 'tool_claude';
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

        // Construction du tableau de messages (historique + message courant)
        $messages = $request->buildMessages();

        // Outils à exposer à Claude
        $tools = $request->tools ?: $this->toolRegistry->definitions();

        $round = 0;
        $totalInputTokens  = 0;
        $totalOutputTokens = 0;

        while ($round < self::MAX_TOOL_ROUNDS) {
            $round++;

            $payload = [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'messages'   => $messages,
            ];

            if ($request->systemPrompt !== null) {
                $payload['system'] = $request->systemPrompt;
            }

            if (! empty($tools)) {
                $payload['tools'] = $tools;
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
                    $this->logError($request, "HTTP {$response->status()}: {$errorBody}");
                    return AIResponse::failure("Erreur API Claude : {$errorBody}", $this->getName(), $model);
                }

                $data = $response->json();
                $usage = $data['usage'] ?? [];
                $totalInputTokens  += $usage['input_tokens']  ?? 0;
                $totalOutputTokens += $usage['output_tokens'] ?? 0;

                $stopReason = $data['stop_reason'] ?? 'end_turn';
                $contentBlocks = $data['content'] ?? [];

                // Ajouter la réponse de l'assistant à l'historique.
                // Cast (object) sur input des blocs tool_use : PHP décode {} en [],
                // ce qui se ré-encoderait en tableau JSON [] au lieu de l'objet {} attendu par l'API.
                $messages[] = ['role' => 'assistant', 'content' => array_map(
                    fn ($block) => isset($block['type']) && $block['type'] === 'tool_use'
                        ? array_merge($block, ['input' => (object) ($block['input'] ?? [])])
                        : $block,
                    $contentBlocks
                )];

                // Fin : Claude a terminé sans appel d'outil
                if ($stopReason === 'end_turn') {
                    $text = $this->extractText($contentBlocks);
                    $this->logInfo($request, $text, $round);

                    return AIResponse::success(
                        content:      $text,
                        provider:     $this->getName(),
                        model:        $data['model'] ?? $model,
                        inputTokens:  $totalInputTokens,
                        outputTokens: $totalOutputTokens,
                    );
                }

                // Claude veut utiliser des outils
                if ($stopReason === 'tool_use') {
                    $toolResults = [];

                    foreach ($contentBlocks as $block) {
                        if (($block['type'] ?? '') !== 'tool_use') {
                            continue;
                        }

                        $toolName   = $block['name'];
                        $toolInput  = $block['input'] ?? [];
                        $toolUseId  = $block['id'];

                        $this->logInfo($request, "Appel outil: {$toolName}", $round);

                        try {
                            $result = $this->toolRegistry->dispatch($toolName, $toolInput);
                        } catch (\Throwable $e) {
                            $result = ['error' => $e->getMessage()];
                        }

                        $toolResults[] = [
                            'type'        => 'tool_result',
                            'tool_use_id' => $toolUseId,
                            'content'     => json_encode($result, JSON_UNESCAPED_UNICODE),
                        ];
                    }

                    // Ajouter les résultats d'outils à l'historique
                    $messages[] = ['role' => 'user', 'content' => $toolResults];
                    continue;
                }

                // Autre stop_reason inattendu → on arrête
                return AIResponse::failure("Stop reason inattendu: {$stopReason}", $this->getName(), $model);

            } catch (\Throwable $e) {
                $this->logError($request, $e->getMessage());
                return AIResponse::failure($e->getMessage(), $this->getName(), $model);
            }
        }

        return AIResponse::failure('Limite de tours d\'outils atteinte.', $this->getName(), $model);
    }

    private function extractText(array $contentBlocks): string
    {
        foreach ($contentBlocks as $block) {
            if (($block['type'] ?? '') === 'text') {
                return $block['text'] ?? '';
            }
        }
        return '';
    }

    private function logInfo(AIRequest $request, string $detail, int $round = 0): void
    {
        if (! config('ai.logging', false)) {
            return;
        }
        Log::channel('daily')->info("[AI:ToolClaude][round={$round}] {$detail}", [
            'module' => $request->metadata['module'] ?? 'unknown',
        ]);
    }

    private function logError(AIRequest $request, string $detail): void
    {
        Log::channel('daily')->error('[AI:ToolClaude] ' . $detail, [
            'module' => $request->metadata['module'] ?? 'unknown',
            'prompt' => mb_substr($request->prompt, 0, 200),
        ]);
    }
}
