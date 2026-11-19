<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Quotes;
use App\Services\Quotes\QuoteDictation;
use App\Services\Visits\VisitTranscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Dictée d'un devis : audio → texte (Whisper OVH, comme le mémo de visite),
 * puis texte → lignes en brouillon. Aucune de ces deux étapes n'écrit en base ;
 * la création des lignes validées est QuoteLinesController::storeDictatedJson.
 */
class QuoteDictationController extends Controller
{
    public function __construct(
        private readonly VisitTranscriptionService $transcription,
        private readonly QuoteDictation $dictation,
    ) {
    }

    /**
     * L'audio reste dans le fichier temporaire de la requête : jamais écrit
     * par l'application, supprimé par PHP à la fin de la requête.
     */
    public function transcribe(Request $request, int $quoteId): JsonResponse
    {
        $this->openQuote($quoteId);

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

    public function propose(Request $request, int $quoteId): JsonResponse
    {
        $this->openQuote($quoteId);

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

    private function openQuote(int $quoteId): Quotes
    {
        $quote = Quotes::withTemplates()->findOrFail($quoteId);
        abort_if($quote->statu != 1, 403);

        return $quote;
    }
}
