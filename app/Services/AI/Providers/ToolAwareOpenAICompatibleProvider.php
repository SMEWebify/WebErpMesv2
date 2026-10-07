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
 * Provider à API OpenAI (/chat/completions) avec tool use — OVHcloud AI Endpoints.
 *
 * Même boucle agentique que ToolAwareClaudeProvider, seul le format change :
 *   - les outils du registre (format Claude : name / description / input_schema)
 *     sont traduits en `{type: function, function: {name, description, parameters}}`
 *   - le system prompt devient un message `system`
 *   - les appels arrivent dans `message.tool_calls` (arguments en chaîne JSON)
 *     et les résultats repartent en messages `role: tool`
 *
 * Le registre d'outils reste unique : ajouter un outil le rend disponible
 * aux deux familles de providers sans rien toucher ici.
 */
class ToolAwareOpenAICompatibleProvider implements AIProviderInterface
{
    private const MAX_TOOL_ROUNDS = 10;

    public function __construct(
        private readonly string             $providerKey,
        private readonly ERPToolRegistry    $toolRegistry,
        private readonly AISettingsResolver $settings,
    ) {}

    public function getName(): string
    {
        return 'tool_' . $this->providerKey;
    }

    public function complete(AIRequest $request): AIResponse
    {
        $config = $this->settings->openAiCompatible($this->providerKey);

        if (empty($config['base_url'])) {
            return AIResponse::failure('URL du endpoint IA non configurée. Renseignez-la dans /admin/integrations/ai.', $this->getName());
        }

        $model     = $request->model     ?? $config['model'];
        $maxTokens = $request->maxTokens ?? $config['max_tokens'];
        $endpoint  = $config['base_url'] . '/chat/completions';

        $messages = [];
        if ($request->systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $request->systemPrompt];
        }
        array_push($messages, ...$request->buildMessages());

        $tools = self::toOpenAITools($request->tools ?: $this->toolRegistry->definitions());

        $round = 0;
        $totalInputTokens  = 0;
        $totalOutputTokens = 0;

        while ($round < self::MAX_TOOL_ROUNDS) {
            $round++;

            $payload = [
                'model'       => $model,
                'max_tokens'  => $maxTokens,
                'messages'    => $messages,
                // Basse température : recommandée par OVH pour le function calling.
                'temperature' => 0.1,
            ];

            if (! empty($tools)) {
                $payload['tools'] = $tools;
            }

            try {
                $http = Http::acceptJson()->timeout($config['timeout']);
                if (! empty($config['api_key'])) {
                    $http = $http->withToken($config['api_key']);
                }

                $response = $http->post($endpoint, $payload);

                if ($response->failed()) {
                    $errorBody = self::errorMessage($response->json(), $response->body());
                    $this->logError($request, "HTTP {$response->status()}: {$errorBody}");
                    return AIResponse::failure("Erreur API {$this->providerKey} : {$errorBody}", $this->getName(), $model);
                }

                $data  = $response->json();
                $usage = $data['usage'] ?? [];
                $totalInputTokens  += $usage['prompt_tokens']     ?? 0;
                $totalOutputTokens += $usage['completion_tokens'] ?? 0;

                $choice    = $data['choices'][0] ?? [];
                $message   = $choice['message'] ?? [];
                $toolCalls = $message['tool_calls'] ?? [];

                // Fin : pas d'appel d'outil (certains serveurs renvoient
                // finish_reason=stop même avec tool_calls, d'où le test sur le contenu).
                if (empty($toolCalls)) {
                    $text = (string) ($message['content'] ?? '');
                    $this->logInfo($request, $text, $round);

                    return AIResponse::success(
                        content:      $text,
                        provider:     $this->getName(),
                        model:        $data['model'] ?? $model,
                        inputTokens:  $totalInputTokens,
                        outputTokens: $totalOutputTokens,
                    );
                }

                $messages[] = [
                    'role'       => 'assistant',
                    'content'    => $message['content'] ?? null,
                    'tool_calls' => $toolCalls,
                ];

                foreach ($toolCalls as $call) {
                    $toolName = $call['function']['name'] ?? '';
                    $rawArgs  = $call['function']['arguments'] ?? '{}';
                    $toolArgs = is_array($rawArgs) ? $rawArgs : (json_decode($rawArgs ?: '{}', true) ?? []);

                    $this->logInfo($request, "Appel outil: {$toolName}", $round);

                    try {
                        $result = $this->toolRegistry->dispatch($toolName, $toolArgs);
                    } catch (\Throwable $e) {
                        $result = ['error' => $e->getMessage()];
                    }

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $call['id'] ?? '',
                        'name'         => $toolName,
                        'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ];
                }

            } catch (\Throwable $e) {
                $this->logError($request, $e->getMessage());
                return AIResponse::failure($e->getMessage(), $this->getName(), $model);
            }
        }

        return AIResponse::failure('Limite de tours d\'outils atteinte.', $this->getName(), $model);
    }

    /**
     * Traduit les définitions du registre (format Claude) au format OpenAI.
     * Un schéma vide est forcé en objet : `[]` serait rejeté comme `parameters`.
     */
    public static function toOpenAITools(array $definitions): array
    {
        return array_map(fn (array $tool) => [
            'type'     => 'function',
            'function' => [
                'name'        => $tool['name'],
                'description' => $tool['description'] ?? '',
                'parameters'  => ! empty($tool['input_schema'])
                    ? $tool['input_schema']
                    : (object) ['type' => 'object', 'properties' => (object) []],
            ],
        ], $definitions);
    }

    /** Les serveurs compatibles OpenAI ne s'accordent pas sur la forme de l'erreur. */
    public static function errorMessage(?array $json, string $body): string
    {
        $error = $json['error'] ?? null;
        if (is_array($error)) {
            return (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE));
        }
        if (is_string($error) && $error !== '') {
            return $error;
        }
        return (string) ($json['message'] ?? $json['detail'] ?? mb_substr($body, 0, 300));
    }

    private function logInfo(AIRequest $request, string $detail, int $round = 0): void
    {
        if (! config('ai.logging', false)) {
            return;
        }
        Log::channel('daily')->info("[AI:{$this->getName()}][round={$round}] {$detail}", [
            'module' => $request->metadata['module'] ?? 'unknown',
        ]);
    }

    private function logError(AIRequest $request, string $detail): void
    {
        Log::channel('daily')->error("[AI:{$this->getName()}] " . $detail, [
            'module' => $request->metadata['module'] ?? 'unknown',
            'prompt' => mb_substr($request->prompt, 0, 200),
        ]);
    }
}
