<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIRequest;
use App\Services\AI\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;

class PythonMLProvider implements AIProviderInterface
{
    private readonly array $config;

    public function __construct()
    {
        $this->config = config('ai.providers.python_ml', []);
    }

    public function getName(): string
    {
        return 'python_ml';
    }

    public function complete(AIRequest $request): AIResponse
    {
        $mode = $this->config['mode'] ?? 'command';

        return match ($mode) {
            'http'    => $this->httpComplete($request),
            'command' => $this->commandComplete($request),
            default   => AIResponse::failure("Mode Python ML inconnu : {$mode}", $this->getName()),
        };
    }

    /**
     * Mode HTTP : appel vers une API FastAPI.
     * Endpoint attendu : POST /complete avec body { "prompt": "...", "context": {...} }
     * Réponse attendue : { "content": "...", "model": "..." }
     */
    private function httpComplete(AIRequest $request): AIResponse
    {
        $baseUrl = $this->config['base_url'] ?? 'http://localhost:8001';
        $timeout = $this->config['timeout'] ?? 60;

        try {
            $response = Http::timeout($timeout)->post("{$baseUrl}/complete", [
                'prompt'  => $request->prompt,
                'context' => $request->context,
            ]);

            if ($response->failed()) {
                return AIResponse::failure(
                    "Erreur API Python : HTTP {$response->status()}",
                    $this->getName()
                );
            }

            $data = $response->json();

            return AIResponse::success(
                content:  $data['content'] ?? '',
                provider: $this->getName(),
                model:    $data['model']   ?? 'python-ml',
            );

        } catch (\Throwable $e) {
            return AIResponse::failure($e->getMessage(), $this->getName());
        }
    }

    /**
     * Mode command : exécution d'un script Python local.
     * Le prompt et le contexte sont passés en JSON via stdin.
     * Le script doit écrire son résultat sur stdout (JSON : { "content": "..." }).
     */
    private function commandComplete(AIRequest $request): AIResponse
    {
        $executable = $this->config['executable'] ?? 'python';
        $script     = $this->config['script']     ?? null;
        $timeout    = $this->config['timeout']    ?? 60;

        if (empty($script) || ! file_exists($script)) {
            return AIResponse::failure(
                'Script Python non configuré ou introuvable (AI_PYTHON_SCRIPT).',
                $this->getName()
            );
        }

        $input = json_encode([
            'prompt'  => $request->prompt,
            'context' => $request->context,
        ]);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            escapeshellcmd($executable) . ' ' . escapeshellarg($script),
            $descriptors,
            $pipes
        );

        if (! is_resource($process)) {
            return AIResponse::failure('Impossible de démarrer le processus Python.', $this->getName());
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $data = json_decode($stdout, true);
        $content = $data['content'] ?? $stdout;

        if (empty($content)) {
            return AIResponse::failure('Le script Python n\'a retourné aucun contenu.', $this->getName());
        }

        return AIResponse::success(
            content:  $content,
            provider: $this->getName(),
            model:    'python-command',
        );
    }
}
