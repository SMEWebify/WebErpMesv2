<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\Workflow\OrderAmendment;
use App\Models\Workflow\Orders;
use App\Services\OrderCalculatorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Avenants d'une commande client.
 *
 * Créer un avenant rouvre la revue de contrat : la revue en place est recopiée
 * dans l'avenant (`previous_review`), puis décision, auteur et date du visa sont
 * vidés sur la commande, qui repasse « revue à faire » et redevient modifiable.
 * Le visa de la revue (ContractReview::decide) est le visa de l'avenant : il le
 * clôt avec la décision, les écarts et le TTC après avenant.
 *
 * Un seul avenant ouvert à la fois. L'annulation, réservée au porteur du visa,
 * remet la revue d'avant. Le devis d'origine n'est jamais touché.
 */
class OrderAmendments
{
    /**
     * @param  array{reason: string, requested_by?: ?int, amendment_date?: ?string}  $data
     *
     * @throws ContractReviewException
     */
    public function create(Orders $order, array $data, User $user): OrderAmendment
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $order = Orders::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! ContractReview::applies($order)) {
                throw new ContractReviewException(__('contract_review.amendment.errors.not_applicable'), 422);
            }
            if ($order->pendingAmendment()->exists()) {
                throw new ContractReviewException(__('contract_review.amendment.errors.already_pending'), 409);
            }

            $amendment = new OrderAmendment();
            $amendment->forceFill([
                'order_id' => $order->id,
                'number' => (int) $order->amendments()->max('number') + 1,
                'reason' => trim($data['reason']),
                'requested_by' => $data['requested_by'] ?? null,
                'amendment_date' => isset($data['amendment_date']) ? Carbon::parse($data['amendment_date']) : today(),
                'status' => OrderAmendment::PENDING,
                'amount_before_ttc' => self::totalTtc($order),
                'previous_review' => [
                    'decision' => $order->review_decision,
                    'reviewed_by' => $order->reviewed_by,
                    'reviewed_at' => $order->reviewed_at?->toDateTimeString(),
                    'gaps' => $order->review_gaps,
                    'checklist' => $order->review_checklist,
                ],
                'created_by' => $user->id,
            ])->save();

            // Revue rouverte : check-list et écarts restent comme base de la nouvelle revue.
            $order->forceFill([
                'review_decision' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ])->save();

            return $amendment;
        });
    }

    /**
     * Annule l'avenant ouvert et remet la revue de contrat telle qu'elle était avant lui.
     *
     * @throws ContractReviewException
     */
    public function cancel(OrderAmendment $amendment, User $user): OrderAmendment
    {
        if (! $user->can(ContractReview::PERMISSION)) {
            throw new ContractReviewException(__('contract_review.errors.forbidden'), 403);
        }

        return DB::transaction(function () use ($amendment, $user) {
            $order = Orders::whereKey($amendment->order_id)->lockForUpdate()->firstOrFail();
            $amendment = OrderAmendment::whereKey($amendment->getKey())->lockForUpdate()->firstOrFail();

            if (! $amendment->isPending()) {
                throw new ContractReviewException(__('contract_review.amendment.errors.closed'), 409);
            }

            $previous = (array) ($amendment->previous_review ?? []);
            $order->forceFill([
                'review_decision' => $previous['decision'] ?? null,
                'reviewed_by' => $previous['reviewed_by'] ?? null,
                'reviewed_at' => $previous['reviewed_at'] ?? null,
                'review_gaps' => $previous['gaps'] ?? null,
                'review_checklist' => $previous['checklist'] ?? null,
            ])->save();

            $amendment->forceFill([
                'status' => OrderAmendment::CANCELLED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ])->save();

            return $amendment;
        });
    }

    /**
     * Clôt l'avenant ouvert au visa de la revue (appelé dans la transaction de ContractReview::decide).
     */
    public function closeOnDecision(Orders $order, string $decision, User $user): void
    {
        $amendment = $order->pendingAmendment()->lockForUpdate()->first();
        if (! $amendment) {
            return;
        }

        $amendment->forceFill([
            'status' => $decision === ContractReview::APPROVED ? OrderAmendment::APPROVED : OrderAmendment::REJECTED,
            'review_decision' => $decision,
            'review_gaps' => $order->review_gaps,
            'reviewed_by' => $user->id,
            'reviewed_at' => $order->reviewed_at ?? now(),
            'amount_after_ttc' => self::totalTtc($order),
        ])->save();
    }

    /**
     * Historique pour l'onglet, du plus récent au plus ancien.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(Orders $order): array
    {
        return $order->amendments()
            ->with(['requester:id,name', 'reviewer:id,name'])
            ->get()
            ->sortByDesc('number')
            ->map(fn (OrderAmendment $a) => [
                'id' => $a->id,
                'number' => $a->number,
                'reason' => $a->reason,
                'requested_by' => $a->requester?->name,
                'date' => $a->amendment_date?->format('d/m/Y'),
                'status' => $a->status,
                'amount_before' => round((float) $a->amount_before_ttc, 2),
                'amount_after' => $a->amount_after_ttc === null ? null : round((float) $a->amount_after_ttc, 2),
                'review_gaps' => $a->review_gaps,
                'reviewed_by' => $a->reviewer?->name,
                'reviewed_at' => $a->reviewed_at?->format('d/m/Y H:i'),
                'cancel_url' => $a->isPending()
                    ? route('orders.json.amendments.cancel', ['id' => $order->id, 'amendment' => $a->id])
                    : null,
            ])
            ->values()
            ->all();
    }

    public static function totalTtc(Orders $order): float
    {
        $order->unsetRelation('orderLines');

        return round((float) (new OrderCalculatorService($order))->getTotalPrice(), 4);
    }
}
