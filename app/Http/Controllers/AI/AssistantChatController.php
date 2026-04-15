<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\Modules\ERPAssistantModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssistantChatController extends Controller
{
    public function __construct(private readonly ERPAssistantModule $assistant) {}

    /**
     * POST /ai/chat
     *
     * Body JSON :
     *   message  : string  — message de l'utilisateur
     *   history  : array   — historique [{role, content}] optionnel
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:1000'],
            'history'         => ['nullable', 'array', 'max:40'],
            'history.*.role'  => ['required', 'in:user,assistant'],
            'history.*.content' => ['required'],
        ]);

        $message = trim($validated['message']);
        $history = $this->sanitizeHistory($validated['history'] ?? []);
        $locale  = app()->getLocale();

        try {
            $response = $this->assistant->chat($message, $history, $locale);
        } catch (\Throwable $e) {
            Log::error('[AI:Chat] Erreur inattendue', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error'   => 'Une erreur est survenue. Réessayez dans un instant.',
            ], 500);
        }

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'error'   => $response->error ?? 'Erreur inconnue.',
            ], 502);
        }

        return response()->json([
            'success'       => true,
            'message'       => $response->content,
            'input_tokens'  => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
        ]);
    }

    /**
     * Sanitize l'historique : on ne garde que role + content texte.
     * Évite d'injecter des blocs tool_use/tool_result côté client.
     */
    private function sanitizeHistory(array $history): array
    {
        return collect($history)
            ->filter(fn ($turn) => in_array($turn['role'] ?? '', ['user', 'assistant'], true))
            ->map(fn ($turn) => [
                'role'    => $turn['role'],
                'content' => is_string($turn['content'])
                    ? mb_substr($turn['content'], 0, 2000)
                    : '',
            ])
            ->values()
            ->toArray();
    }
}
