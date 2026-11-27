<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Orders;
use App\Services\Quotes\QuoteDictation;
use App\Services\Visits\VisitTranscriptionService;
use App\Services\Workflow\OrderDictation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Dictée d'une commande : mêmes étapes que la dictée de devis (audio → texte,
 * texte → lignes en brouillon), sans rien écrire. La création des lignes
 * validées est OrderLinesController::storeDictatedJson.
 */
class OrderDictationController extends Controller
{
    public function __construct(
        private readonly VisitTranscriptionService $transcription,
        private readonly QuoteDictation $dictation,
        private readonly OrderDictation $orderDictation,
    ) {
    }

    /** L'audio reste dans le fichier temporaire de la requête, jamais écrit. */
    public function transcribe(Request $request, int $orderId): JsonResponse
    {
        if ($refusal = $this->refusal($orderId)) {
            return $refusal;
        }

        $request->validate([
            'audio' => [
                'required', 'file',
                'max:' . (int) config('ai.transcription.max_size', 25600),
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/wav,audio/x-wav,application/octet-stream',
            ],
        ]);

        try {
            $text = $this->transcription->transcribe($request->file('audio'), app()->getLocale());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['text' => $text]);
    }

    public function propose(Request $request, int $orderId): JsonResponse
    {
        if ($refusal = $this->refusal($orderId)) {
            return $refusal;
        }

        $validated = $request->validate([
            'text' => 'required|string|max:' . QuoteDictation::MAX_TEXT,
        ]);

        try {
            $lines = $this->dictation->propose($validated['text']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['lines' => $lines]);
    }

    private function refusal(int $orderId): ?JsonResponse
    {
        $message = $this->orderDictation->refusal(Orders::findOrFail($orderId));

        return $message === null ? null : response()->json(['message' => $message], 403);
    }
}
