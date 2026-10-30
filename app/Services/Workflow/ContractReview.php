<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\Workflow\OrderAmendment;
use App\Models\Workflow\Orders;
use App\Services\Affairs\AffairRiskRegister;
use App\Services\Files\FileRole;
use App\Services\OrderConfirmationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

/**
 * Revue de contrat d'une commande client (ISO 19443 / ISO 9001 § 8.2.3).
 *
 * Préparation libre : check-list (config/orders.php) et écarts avec l'offre,
 * modifiables par tout utilisateur du module tant que la revue n'est pas visée.
 * Visa = décision « validée » ou « rejetée », réservé à `contract-review-approve` ;
 * reviewed_by / reviewed_at sont posés ici, jamais saisis. Une revue visée est
 * figée (409). Une validation dont un point n'est pas coché, comme un rejet,
 * exige que les écarts soient décrits.
 *
 * Rien ne bloque la production sur une revue manquante : l'écran avertit seulement.
 * Une revue rejetée garde le refus de BPF existant (ProductionRelease).
 *
 * Un avenant (OrderAmendments) rouvre la revue ; le visa suivant le clôt.
 *
 * Registre des risques : sur une catégorie d'affaire qui l'exige, l'état porte un
 * avertissement tant qu'aucun risque n'est recensé (AffairRiskRegister) — le visa
 * reste possible.
 */
class ContractReview
{
    public const PERMISSION = 'contract-review-approve';

    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const DECISIONS = [self::APPROVED, self::REJECTED];

    // Indicateur de l'index des commandes
    public const STATE_DONE = 'done';
    public const STATE_REJECTED = 'rejected';
    public const STATE_TODO = 'todo';

    public function __construct(
        private OrderConfirmationService $confirmations,
        private OrderAmendments $amendments,
        private AffairRiskRegister $risks,
    ) {
    }

    /**
     * Points de la check-list, dans l'ordre de config/orders.php.
     *
     * @return array<int, string>
     */
    public static function items(): array
    {
        return array_values(array_filter((array) config('orders.review_checklist', []), 'is_string'));
    }

    public static function itemLabel(string $key): string
    {
        $transKey = 'contract_review.checklist.' . $key;

        return Lang::has($transKey) ? __($transKey) : $key;
    }

    /** Seule une commande client (type 1) passe une revue de contrat. */
    public static function applies(Orders $order): bool
    {
        return (int) $order->type === 1;
    }

    public static function isFrozen(Orders $order): bool
    {
        return in_array($order->review_decision, self::DECISIONS, true);
    }

    /**
     * État de l'index : null pour une commande interne. Un ancien « pending » vaut « à faire ».
     */
    public static function stateFor(?string $decision, int $type): ?string
    {
        if ($type !== 1) {
            return null;
        }

        return match ($decision) {
            self::APPROVED => self::STATE_DONE,
            self::REJECTED => self::STATE_REJECTED,
            default => self::STATE_TODO,
        };
    }

    public function canApprove(?User $user): bool
    {
        return (bool) $user?->can(self::PERMISSION);
    }

    /**
     * Enregistre la préparation (check-list, écarts) sans viser.
     *
     * @param  array{review_gaps?: ?string, review_checklist?: array<string, mixed>}  $data
     *
     * @throws ContractReviewException
     */
    public function saveDraft(Orders $order, array $data): Orders
    {
        return DB::transaction(function () use ($order, $data) {
            $order = $this->lockedFresh($order);
            $this->assertEditable($order);

            $this->fillDraft($order, $data);
            $order->save();

            return $order;
        });
    }

    /**
     * Vise la revue. La préparation envoyée avec la décision est enregistrée en même temps.
     *
     * @param  array{review_gaps?: ?string, review_checklist?: array<string, mixed>}  $data
     *
     * @throws ContractReviewException
     */
    public function decide(Orders $order, string $decision, User $user, array $data = []): Orders
    {
        if (! $this->canApprove($user)) {
            throw new ContractReviewException(__('contract_review.errors.forbidden'), 403);
        }
        if (! in_array($decision, self::DECISIONS, true)) {
            throw new ContractReviewException(__('contract_review.errors.invalid_decision'), 422);
        }

        $order = DB::transaction(function () use ($order, $decision, $user, $data) {
            $order = $this->lockedFresh($order);
            $this->assertEditable($order);

            $this->fillDraft($order, $data);

            if (blank($order->review_gaps)) {
                if ($decision === self::REJECTED) {
                    throw new ContractReviewException(__('contract_review.errors.gaps_required_reject'), 422);
                }
                $unchecked = $this->uncheckedItems($order);
                if ($unchecked !== []) {
                    throw new ContractReviewException(__('contract_review.errors.gaps_required_unchecked', [
                        'items' => implode(', ', array_map([self::class, 'itemLabel'], $unchecked)),
                    ]), 422);
                }
            }

            $order->forceFill([
                'review_decision' => $decision,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ])->save();

            $this->amendments->closeOnDecision($order, $decision, $user);

            return $order;
        });

        // La revue validée constitue l'ARC (comportement repris d'OrdersController::update).
        if ($decision === self::APPROVED) {
            $this->confirmations->createFromOrder($order, $user->id);
        }

        return $order;
    }

    /**
     * Points configurés non cochés.
     *
     * @return array<int, string>
     */
    public function uncheckedItems(Orders $order): array
    {
        $checklist = (array) ($order->review_checklist ?? []);

        return array_values(array_filter(self::items(), fn (string $key) => empty($checklist[$key])));
    }

    /**
     * État de la revue pour l'écran.
     *
     * @return array<string, mixed>
     */
    public function state(Orders $order, ?User $viewer): array
    {
        $order->loadMissing(['reviewer:id,name', 'changeRequester:id,name']);
        $amendments = $this->amendments->history($order);
        $pending = collect($amendments)->firstWhere('status', OrderAmendment::PENDING);
        $legacyPending = $order->change_requested_by !== null && $order->change_approved_at === null;
        $checklist = (array) ($order->review_checklist ?? []);

        // Points configurés, puis ceux d'une revue passée retirés depuis de la config.
        $keys = array_values(array_unique(array_merge(self::items(), array_keys($checklist))));

        return [
            'applicable' => self::applies($order),
            'status' => self::stateFor($order->review_decision, (int) $order->type),
            'decision' => in_array($order->review_decision, self::DECISIONS, true) ? $order->review_decision : null,
            'frozen' => self::isFrozen($order),
            'gaps' => $order->review_gaps,
            'checklist' => array_map(fn (string $key) => [
                'key' => $key,
                'label' => self::itemLabel($key),
                'checked' => ! empty($checklist[$key]),
                'configured' => in_array($key, self::items(), true),
            ], $keys),
            'reviewed_by' => $order->reviewer?->name,
            'reviewed_at' => $order->reviewed_at?->format('d/m/Y H:i'),
            'can_approve' => $this->canApprove($viewer),
            'documents' => $order->files()
                ->wherePivot('role', FileRole::CONTRACTUAL)
                ->get(['files.id', 'files.name', 'files.original_file_name'])
                ->map(fn ($file) => [
                    'id' => $file->id,
                    'name' => $file->original_file_name ?: $file->name,
                    'url' => route('files.raw', ['file' => $file->id]),
                ])
                ->values()
                ->all(),
            'amendments' => $amendments,
            'pending_amendment' => $pending ? ['id' => $pending['id'], 'number' => $pending['number']] : null,
            // Ancienne demande de changement (colonnes d'orders) : lecture seule, pour l'historique.
            'legacy_change' => ($order->change_requested_by || $order->change_reason || $order->change_approved_at) ? [
                'requested_by' => $order->changeRequester?->name,
                'reason' => $order->change_reason,
                'approved_at' => $order->change_approved_at?->format('d/m/Y H:i'),
                'pending' => $legacyPending,
            ] : null,
            'change' => [
                'pending' => $pending !== null || $legacyPending,
            ],
            // Avertissement seulement (null = rien à signaler ou option inactive).
            'risk_register' => self::applies($order) ? $this->risks->contractReviewWarning($order) : null,
        ];
    }

    private function fillDraft(Orders $order, array $data): void
    {
        if (array_key_exists('review_gaps', $data)) {
            $gaps = is_string($data['review_gaps']) ? trim($data['review_gaps']) : null;
            $order->review_gaps = $gaps === '' ? null : $gaps;
        }

        if (array_key_exists('review_checklist', $data) && is_array($data['review_checklist'])) {
            $checklist = (array) ($order->review_checklist ?? []);
            foreach (self::items() as $key) {
                if (array_key_exists($key, $data['review_checklist'])) {
                    $checklist[$key] = filter_var($data['review_checklist'][$key], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $order->review_checklist = $checklist;
        }
    }

    private function assertEditable(Orders $order): void
    {
        if (! self::applies($order)) {
            throw new ContractReviewException(__('contract_review.errors.not_applicable'), 422);
        }
        if (self::isFrozen($order)) {
            throw new ContractReviewException(__('contract_review.errors.frozen'), 409);
        }
    }

    private function lockedFresh(Orders $order): Orders
    {
        return Orders::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }
}
