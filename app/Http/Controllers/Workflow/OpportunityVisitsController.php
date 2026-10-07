<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunityVisits;
use App\Services\Files\FileKindResolver;
use App\Services\Visits\VisitReportService;
use App\Services\Visits\VisitTranscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Écran « Visite sur site », pensé pour le téléphone : photos, cotes, notes,
 * mémo vocal transcrit et compte rendu, puis validation en événement
 * « Visite sur site » de l'opportunité.
 */
class OpportunityVisitsController extends Controller
{
    public function __construct(
        private readonly VisitTranscriptionService $transcription,
        private readonly VisitReportService $reports,
    ) {
    }

    /**
     * Page mobile. Reprend le brouillon en cours de l'utilisateur sur cette
     * opportunité, ou la visite demandée par ?visit= (lecture seule si validée).
     */
    public function show(Request $request, Opportunities $id)
    {
        $opportunity = $id->loadMissing('companie:id,label', 'contact', 'adresse');

        $visit = $request->filled('visit')
            ? $opportunity->visits()->findOrFail((int) $request->query('visit'))
            : $opportunity->visits()
                ->where('user_id', Auth::id())
                ->where('statu', OpportunityVisits::STATU_DRAFT)
                ->latest('id')
                ->first();

        return view('workflow/opportunity-visit', [
            'Opportunity' => $opportunity,
            'visit' => $visit ? $this->present($visit) : null,
            'transcriptionAvailable' => $this->transcription->isAvailable(),
            'imageAccept' => collect(FileKindResolver::extensionsOf(FileKindResolver::KIND_IMAGE))
                ->map(fn (string $extension) => '.' . $extension)
                ->implode(','),
        ]);
    }

    public function store(Opportunities $id): JsonResponse
    {
        $visit = $id->visits()->create([
            'user_id' => Auth::id(),
            'visited_at' => now(),
            'statu' => OpportunityVisits::STATU_DRAFT,
        ]);

        return response()->json(['visit' => $this->present($visit)], 201);
    }

    public function update(Request $request, OpportunityVisits $visit): JsonResponse
    {
        $this->authorizeDraft($visit);

        $data = $request->validate([
            'visited_at' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'transcript' => ['sometimes', 'nullable', 'string', 'max:100000'],
            'report' => ['sometimes', 'nullable', 'string', 'max:100000'],
            'measurements' => ['sometimes', 'nullable', 'array', 'max:200'],
            'measurements.*.label' => ['nullable', 'string', 'max:255'],
            'measurements.*.value' => ['nullable', 'string', 'max:50'],
            'measurements.*.unit' => ['nullable', 'string', 'max:10'],
            'measurements.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('measurements', $data)) {
            $data['measurements'] = $this->cleanMeasurements($data['measurements'] ?? []);
        }

        $visit->update($data);

        return response()->json(['visit' => $this->present($visit->fresh())]);
    }

    /**
     * Transcrit un mémo et l'ajoute à la suite de la transcription existante.
     * Le fichier audio n'est pas déplacé hors du répertoire temporaire de PHP :
     * il disparaît à la fin de la requête, transcription réussie ou non.
     */
    public function transcribe(Request $request, OpportunityVisits $visit): JsonResponse
    {
        $this->authorizeDraft($visit);

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

        if ($text !== '') {
            $visit->update([
                'transcript' => trim(($visit->transcript ?? '') . "\n\n" . $text),
            ]);
        }

        return response()->json([
            'text' => $text,
            'visit' => $this->present($visit->fresh()),
        ]);
    }

    public function report(OpportunityVisits $visit): JsonResponse
    {
        $this->authorizeDraft($visit);

        try {
            $report = $this->reports->draft($visit);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $visit->update(['report' => $report]);

        return response()->json(['visit' => $this->present($visit->fresh())]);
    }

    /**
     * Fige la visite et la verse dans les événements de l'opportunité, où le
     * calendrier commercial et la timeline la retrouvent.
     */
    public function validateVisit(OpportunityVisits $visit): JsonResponse
    {
        $this->authorizeDraft($visit);

        $summary = filled($visit->report) ? $visit->report : $visit->notes;

        if (blank($summary)) {
            return response()->json(['message' => __('visits.validate_needs_report')], 422);
        }

        DB::transaction(function () use ($visit, $summary) {
            $event = OpportunitiesEventsLogs::create([
                'opportunities_id' => $visit->opportunities_id,
                'user_id' => $visit->user_id,
                'label' => __('visits.event_label', ['date' => $visit->visited_at->format('d/m/Y')]),
                'type' => 3,
                'start_date' => $visit->visited_at->toDateString(),
                'end_date' => $visit->visited_at->toDateString(),
                'comment' => $summary,
            ]);

            $visit->update([
                'opportunities_events_logs_id' => $event->id,
                'statu' => OpportunityVisits::STATU_VALIDATED,
            ]);
        });

        return response()->json([
            'visit' => $this->present($visit->fresh()),
            'redirect' => route('opportunities.show', $visit->opportunities_id) . '#Events',
        ]);
    }

    /**
     * Abandon d'un brouillon. Les photos déjà envoyées restent dans la GED de
     * l'opportunité : ce sont des documents, pas un état de l'écran.
     */
    public function destroy(OpportunityVisits $visit): JsonResponse
    {
        $this->authorizeDraft($visit);

        $visit->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Un brouillon appartient à celui qui fait la visite : il contient sa
     * dictée. L'admin garde la main pour débloquer un brouillon orphelin.
     */
    private function authorizeDraft(OpportunityVisits $visit): void
    {
        $user = Auth::user();

        abort_unless(
            $visit->user_id === null
            || $visit->user_id === $user->id
            || $user->hasRole('Admin'),
            403
        );

        abort_unless($visit->isDraft(), 409, __('visits.already_validated'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, string|null>>
     */
    private function cleanMeasurements(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => [
                'label' => trim((string) ($row['label'] ?? '')) ?: null,
                'value' => trim((string) ($row['value'] ?? '')) ?: null,
                'unit' => trim((string) ($row['unit'] ?? '')) ?: null,
                'note' => trim((string) ($row['note'] ?? '')) ?: null,
            ])
            ->filter(fn ($row) => $row['label'] !== null || $row['value'] !== null)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(OpportunityVisits $visit): array
    {
        $visit->loadMissing('user:id,name');

        return [
            'id' => $visit->id,
            'visited_at' => $visit->visited_at?->format('Y-m-d\TH:i'),
            'notes' => $visit->notes,
            'measurements' => $visit->measurements ?? [],
            'transcript' => $visit->transcript,
            'report' => $visit->report,
            'statu' => $visit->statu,
            'is_draft' => $visit->isDraft(),
            'user' => $visit->user?->name,
            'photo_hashtag' => $visit->photoHashtag(),
            'endpoints' => [
                'update' => route('opportunities.visits.update', $visit),
                'transcribe' => route('opportunities.visits.transcribe', $visit),
                'report' => route('opportunities.visits.report', $visit),
                'validate' => route('opportunities.visits.validate', $visit),
                'destroy' => route('opportunities.visits.destroy', $visit),
            ],
        ];
    }
}
