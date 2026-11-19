<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Appel direct « texte → texte » au provider choisi dans /admin/integrations/ai,
 * sans le registre d'outils de l'assistant (même patron que le compte rendu de
 * visite et les lecteurs de justificatifs).
 *
 * Utilisé par la dictée de devis et la reformulation : rien n'est enregistré
 * ici, la réponse est toujours relue par l'utilisateur.
 */
class DirectTextCompletion
{
    public function __construct(private readonly AISettingsResolver $settings)
    {
    }

    /** Claude exige une clé ; une API compatible OpenAI, une URL (OVH accepte l'accès anonyme). */
    public function isAvailable(): bool
    {
        $provider = $this->settings->activeProvider();

        return $provider === 'claude'
            ? ! empty($this->settings->claude()['api_key'])
            : ! empty($this->settings->openAiCompatible($provider)['base_url']);
    }

    /** 'claude' ou 'ovh' : sert à dire à l'utilisateur où partent ses données. */
    public function provider(): string
    {
        return $this->settings->activeProvider();
    }

    /**
     * @throws RuntimeException message affichable tel quel à l'utilisateur
     */
    public function complete(string $system, string $prompt, int $maxTokens = 1500, float $temperature = 0.0): string
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException(__('ai_text.unavailable'));
        }

        $provider = $this->settings->activeProvider();

        try {
            $response = $provider === 'claude'
                ? $this->viaClaude($system, $prompt, $maxTokens, $temperature)
                : $this->viaOpenAiCompatible($provider, $system, $prompt, $maxTokens, $temperature);
        } catch (\Throwable $e) {
            Log::warning('AI text completion unreachable', ['provider' => $provider, 'error' => $e->getMessage()]);
            throw new RuntimeException(__('ai_text.failed'));
        }

        if ($response->failed()) {
            Log::warning('AI text completion failed', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException(__('ai_text.failed'));
        }

        return trim((string) ($provider === 'claude'
            ? $response->json('content.0.text', '')
            : $response->json('choices.0.message.content', '')));
    }

    private function viaClaude(string $system, string $prompt, int $maxTokens, float $temperature)
    {
        $config = $this->settings->claude();

        return Http::withHeaders([
            'x-api-key' => $config['api_key'],
            'anthropic-version' => $this->settings->claudeApiVersion(),
        ])
            ->acceptJson()
            ->timeout(max(60, (int) $config['timeout']))
            ->post($this->settings->claudeEndpoint(), [
                'model' => $config['model'],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
    }

    private function viaOpenAiCompatible(string $provider, string $system, string $prompt, int $maxTokens, float $temperature)
    {
        $config = $this->settings->openAiCompatible($provider);

        $http = Http::acceptJson()->timeout(max(60, (int) $config['timeout']));
        if (! empty($config['api_key'])) {
            $http = $http->withToken($config['api_key']);
        }

        return $http->post($config['base_url'] . '/chat/completions', [
            'model' => $config['model'],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
    }
}
