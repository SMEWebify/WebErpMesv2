<?php

namespace App\Services\Workflow;

use App\Models\Workflow\LossReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Motif de perte d'un devis (statut 4) ou d'une opportunité (statut 5).
 *
 * Le motif est exigé quand le document ENTRE dans le statut perdu et qu'au moins
 * un motif est actif. Un document déjà perdu (avant la mise en place des motifs)
 * reste valide sans motif : le renseigner après coup est facultatif.
 *
 * Un refus est une 422 qui porte, en plus des erreurs, la configuration de la
 * fenêtre (`loss_reason`) : l'écran l'ouvre et renvoie la requête complétée
 * (resources/js/lib/lossReason.jsx), quel que soit l'endroit d'où vient le
 * changement de statut (flèches de la fiche, kanban).
 */
final class LossReasonRecorder
{
    public const QUOTE_LOST       = 4;
    public const OPPORTUNITY_LOST = 5;

    public const KIND_QUOTE       = 'quote';
    public const KIND_OPPORTUNITY = 'opportunity';

    /**
     * Champs de perte à écrire avec le nouveau statut ; [] quand il n'y a rien à
     * écrire (autre statut, ou aucun motif envoyé alors qu'il est facultatif).
     */
    public function fieldsForStatusChange(Request $request, Model $document, int $newStatu, string $kind): array
    {
        $lost = self::lostStatus($kind);
        if ($newStatu !== $lost) {
            return [];
        }

        $entering = (int) $document->statu !== $lost;
        $required = $entering && LossReason::active()->exists();

        if (! $required && ! $request->filled('loss_reason_id') && ! $request->filled('loss_comment')) {
            return [];
        }

        return $this->validate($request, $document, $kind, $required);
    }

    /** Saisie ou correction du motif d'un document déjà perdu. */
    public function record(Request $request, Model $document, string $kind): void
    {
        if ((int) $document->statu !== self::lostStatus($kind)) {
            throw new HttpResponseException(response()->json([
                'message' => __('losses.not_lost'),
            ], 422));
        }

        $document->update($this->validate($request, $document, $kind, LossReason::active()->exists()));
    }

    /** Configuration de la fenêtre de saisie (textes compris : elle s'ouvre depuis plusieurs écrans). */
    public function dialog(Model $document, string $kind, ?bool $required = null): array
    {
        $currentId = $document->loss_reason_id ? (int) $document->loss_reason_id : null;

        return [
            'kind'     => $kind,
            'status'   => self::lostStatus($kind),
            'required' => $required ?? LossReason::active()->exists(),
            'reasons'  => LossReason::choices($currentId)
                ->map(fn (LossReason $r) => ['id' => $r->id, 'label' => $r->label, 'active' => $r->active])
                ->values()->all(),
            'current'  => [
                'loss_reason_id' => $currentId,
                'loss_comment'   => $document->loss_comment,
                'label'          => $currentId ? LossReason::whereKey($currentId)->value('label') : null,
            ],
            'texts'    => array_merge(__('losses.dialog'), [
                'title' => __('losses.dialog.title_'.$kind),
                'label' => __('losses.label'),
                'none'  => __('losses.none'),
            ]),
        ];
    }

    public static function lostStatus(string $kind): int
    {
        return $kind === self::KIND_OPPORTUNITY ? self::OPPORTUNITY_LOST : self::QUOTE_LOST;
    }

    private function validate(Request $request, Model $document, string $kind, bool $required): array
    {
        $currentId = $document->loss_reason_id ? (int) $document->loss_reason_id : null;
        $allowed   = LossReason::choices($currentId)->pluck('id')->all();

        $validator = Validator::make($request->all(), [
            'loss_reason_id' => [$required ? 'required' : 'nullable', 'integer', Rule::in($allowed)],
            'loss_comment'   => ['nullable', 'string', 'max:2000'],
        ], [
            'loss_reason_id.required' => __('losses.required'),
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message'     => $validator->errors()->first(),
                'errors'      => $validator->errors(),
                'loss_reason' => $this->dialog($document, $kind, $required),
            ], 422));
        }

        $data = $validator->validated();
        $comment = trim((string) ($data['loss_comment'] ?? ''));

        return [
            'loss_reason_id' => isset($data['loss_reason_id']) ? (int) $data['loss_reason_id'] : null,
            'loss_comment'   => $comment !== '' ? $comment : null,
        ];
    }
}
