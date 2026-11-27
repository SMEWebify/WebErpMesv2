<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Services\Crm\LeadDictation;
use App\Services\Visits\VisitTranscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Dictée d'une piste : audio → texte (Whisper OVH), texte → fiche en brouillon,
 * puis enregistrement de la fiche relue. Les trois portes exigent l'autorisation
 * d'envoyer des données personnelles (carte IA) : sans elle, 403.
 */
class LeadDictationController extends Controller
{
    public function __construct(
        private readonly LeadDictation $dictation,
        private readonly VisitTranscriptionService $transcription,
    ) {
    }

    /** L'audio reste dans le fichier temporaire de la requête, jamais écrit. */
    public function transcribe(Request $request): JsonResponse
    {
        if ($refusal = $this->refusal()) {
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

    public function propose(Request $request): JsonResponse
    {
        if ($refusal = $this->refusal()) {
            return $refusal;
        }

        $validated = $request->validate([
            'text' => 'required|string|max:' . LeadDictation::MAX_TEXT,
        ]);

        try {
            $draft = $this->dictation->propose($validated['text']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['draft' => $draft]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($refusal = $this->refusal()) {
            return $refusal;
        }

        $validated = $request->validate([
            'companies_id'          => 'nullable|integer|exists:companies,id',
            'company_label'         => 'required_without:companies_id|nullable|string|max:255',
            'companies_contacts_id' => [
                'nullable', 'integer',
                Rule::exists('companies_contacts', 'id')->where('companies_id', (int) $request->input('companies_id')),
            ],
            'contact_name'          => 'required_without:companies_contacts_id|nullable|string|max:255',
            'contact_first_name'    => 'nullable|string|max:255',
            'contact_function'      => 'nullable|string|max:255',
            'contact_mail'          => 'nullable|email|max:255',
            'contact_number'        => 'nullable|string|max:50',
            'subject'               => 'nullable|string|max:255',
            'comment'               => 'nullable|string|max:' . LeadDictation::MAX_TEXT,
            'source'                => 'required|string|max:255',
            'priority'              => 'required|integer|between:1,4',
        ]);

        $lead = $this->dictation->create($validated, $request->user()->id);

        return response()->json([
            'message'  => __('lead_dictation.created'),
            'redirect' => route('leads.show', ['id' => $lead->id]),
        ], 201);
    }

    private function refusal(): ?JsonResponse
    {
        return $this->dictation->isAvailable()
            ? null
            : response()->json(['message' => __('lead_dictation.unavailable')], 403);
    }
}
