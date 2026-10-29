<?php

namespace App\Services\Purchases;

use App\Models\Companies\SupplierQualificationOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fournisseur qualifié = dernière évaluation formelle (supplier_ratings hors notes de
 * réception) au statut « approved », dont la date de revue n'est pas dépassée (ou nulle).
 *
 * Option `purchases.require_qualified_supplier` (faux par défaut) : inactive, aucun
 * contrôle ni badge. Active, une commande d'achat n'est créée ni validée (passage de
 * « en cours » à commandée / reçue) pour un fournisseur non qualifié, sauf passe-droit
 * motivé d'un porteur de `unqualified-supplier-override`, qui vaut pour cette seule
 * commande. Les demandes de prix ne sont jamais contrôlées.
 */
class SupplierQualification
{
    public const QUALIFIED      = 'qualified';
    public const REVIEW_OVERDUE = 'review_overdue';
    public const UNQUALIFIED    = 'unqualified';

    public const OVERRIDE_PERMISSION = 'unqualified-supplier-override';

    /** Statuts d'une commande d'achat validée : commandée, reçue en partie, reçue. */
    public const VALIDATED_STATUSES = [2, 3, 4];

    public const CONTEXT_CREATE          = 'create';
    public const CONTEXT_VALIDATE        = 'validate';
    public const CONTEXT_SUPPLIER_CHANGE = 'supplier_change';

    public function enabled(): bool
    {
        return (bool) config('purchases.require_qualified_supplier');
    }

    /**
     * État de qualification de chaque fournisseur, en une requête.
     *
     * @param  iterable<int>  $companyIds
     * @return Collection<int, array{status:string, evaluation_status:?string, approved_at:?string, next_review_at:?string, evaluated_at:?string}>
     */
    public function states(iterable $companyIds): Collection
    {
        $ids = collect($companyIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $latest = DB::table('supplier_ratings')
            ->whereIn('companies_id', $ids->all())
            ->whereNull('purchase_receipt_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['companies_id', 'evaluation_status', 'approved_at', 'next_review_at', 'created_at'])
            ->unique('companies_id')
            ->keyBy('companies_id');

        return $ids->mapWithKeys(fn (int $id) => [$id => $this->resolve($latest->get($id))]);
    }

    public function stateOf(int $companyId): array
    {
        return $this->states([$companyId])->get($companyId);
    }

    /** Statut par fournisseur pour les sélecteurs ; vide quand l'option est inactive. */
    public function statusMap(iterable $companyIds): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->states($companyIds)->map(fn (array $state) => $state['status'])->all();
    }

    /** Textes des badges, pour les écrans qui affichent l'état d'un fournisseur. */
    public function badgeConfig(): array
    {
        return [
            'enabled' => $this->enabled(),
            'texts'   => [
                self::QUALIFIED      => __('supplier_qualification.status.qualified'),
                self::UNQUALIFIED    => __('supplier_qualification.status.unqualified'),
                self::REVIEW_OVERDUE => __('supplier_qualification.status.review_overdue'),
            ],
        ];
    }

    /** Contrôle d'une commande à créer pour ce fournisseur. */
    public function check(?int $companyId): SupplierCheck
    {
        return $this->checkMany($companyId ? [$companyId] : []);
    }

    /**
     * Contrôle de plusieurs fournisseurs à la fois (réapprovisionnement : une commande
     * par fournisseur, un seul motif pour toutes).
     *
     * @param  iterable<int>  $companyIds
     */
    public function checkMany(iterable $companyIds, string $context = self::CONTEXT_CREATE): SupplierCheck
    {
        if (! $this->enabled()) {
            return new SupplierCheck([], $context);
        }

        $blocked = $this->states($companyIds)
            ->reject(fn (array $state) => $state['status'] === self::QUALIFIED)
            ->all();

        return new SupplierCheck($blocked, $context);
    }

    /**
     * Contrôle d'un changement de statut : seul le passage d'une commande non validée
     * à un statut validé est contrôlé. Un passe-droit déjà donné à cette commande pour
     * ce fournisseur (à la création) vaut aussi pour sa validation.
     */
    public function checkValidation(Model $purchase, int $newStatu): SupplierCheck
    {
        if (! $this->enabled()
            || ! in_array($newStatu, self::VALIDATED_STATUSES, true)
            || in_array((int) $purchase->statu, self::VALIDATED_STATUSES, true)
            || $this->hasOverride($purchase, (int) $purchase->companies_id)) {
            return new SupplierCheck([], self::CONTEXT_VALIDATE);
        }

        return $this->checkMany([(int) $purchase->companies_id], self::CONTEXT_VALIDATE);
    }

    /** Changement de fournisseur d'une commande déjà validée. */
    public function checkSupplierChange(Model $purchase, ?int $newCompanyId): SupplierCheck
    {
        if (! $newCompanyId
            || (int) $purchase->companies_id === $newCompanyId
            || ! in_array((int) $purchase->statu, self::VALIDATED_STATUSES, true)) {
            return new SupplierCheck([], self::CONTEXT_SUPPLIER_CHANGE);
        }

        return $this->checkMany([$newCompanyId], self::CONTEXT_SUPPLIER_CHANGE);
    }

    /**
     * Point de contrôle des écrans React. Null : continuer ; sinon la réponse
     * à renvoyer telle quelle (422 portant `supplier_qualification`).
     */
    public function guardJson(Request $request, SupplierCheck $check): ?JsonResponse
    {
        $refusal = $this->decide($request, $check);
        if ($refusal === null) {
            return null;
        }

        return response()->json([
            'message'                => $refusal['message'],
            'errors'                 => $refusal['errors'] ?? null,
            'supplier_qualification' => $this->payload($check, $request->user()),
        ], $refusal['status']);
    }

    /** Même contrôle pour un formulaire : null pour continuer, sinon le message d'erreur. */
    public function guardForm(Request $request, SupplierCheck $check): ?string
    {
        return $this->decide($request, $check)['message'] ?? null;
    }

    /** Trace le passe-droit, s'il y en a eu un, sur la commande créée ou validée. */
    public function recordOverride(SupplierCheck $check, Model $document): void
    {
        $companyId = (int) $document->companies_id;
        $state = $check->blocked[$companyId] ?? null;

        if ($check->overrideReason === null || $state === null) {
            return;
        }

        SupplierQualificationOverride::create([
            'companies_id'         => $companyId,
            'document_type'        => $document->getMorphClass(),
            'document_id'          => $document->getKey(),
            'user_id'              => auth()->id(),
            'context'              => $check->context,
            'reason'               => $check->overrideReason,
            'qualification_status' => $state['status'],
            'evaluation_status'    => $state['evaluation_status'],
            'next_review_at'       => $state['next_review_at'],
        ]);
    }

    public function hasOverride(Model $document, int $companyId): bool
    {
        return SupplierQualificationOverride::where('document_type', $document->getMorphClass())
            ->where('document_id', $document->getKey())
            ->where('companies_id', $companyId)
            ->exists();
    }

    /**
     * Données de la fenêtre de refus côté écran, textes compris : elle s'ouvre
     * depuis plusieurs écrans qui n'ont pas les mêmes traductions.
     */
    public function payload(SupplierCheck $check, ?User $user): array
    {
        $labels = DB::table('companies')->whereIn('id', array_keys($check->blocked))->pluck('label', 'id');

        return [
            'suppliers' => collect($check->blocked)->map(fn (array $state, int $id) => [
                'id'           => $id,
                'label'        => $labels[$id] ?? "#{$id}",
                'status'       => $state['status'],
                'status_label' => __('supplier_qualification.status.' . $state['status']),
                'detail'       => $this->detail($state),
            ])->values()->all(),
            'can_override' => (bool) $user?->can(self::OVERRIDE_PERMISSION),
            'texts'        => [
                'title'         => __('supplier_qualification.blocked_title'),
                'message'       => __('supplier_qualification.blocked_' . $check->context),
                'no_permission' => __('supplier_qualification.no_permission'),
                'reason_label'  => __('supplier_qualification.override_reason'),
                'reason_help'   => __('supplier_qualification.override_reason_help'),
                'override'      => __('supplier_qualification.override'),
                'cancel'        => __('general_content.cancel_trans_key'),
            ],
        ];
    }

    /** Explication d'un état non qualifié, pour la fenêtre et le message d'erreur. */
    public function detail(array $state): string
    {
        if ($state['status'] === self::REVIEW_OVERDUE) {
            return __('supplier_qualification.detail.review_overdue', [
                'date' => Carbon::parse($state['next_review_at'])->format('d/m/Y'),
            ]);
        }

        if ($state['evaluation_status'] === null) {
            return __('supplier_qualification.detail.no_evaluation');
        }

        return __('supplier_qualification.detail.not_approved', [
            'status' => $this->evaluationStatusLabel($state['evaluation_status']),
        ]);
    }

    public function evaluationStatusLabel(?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        $key = "general_content.evaluation_status_{$status}_trans_key";
        $label = __($key);

        return $label === $key ? ucfirst(str_replace('_', ' ', $status)) : $label;
    }

    /**
     * @return array{status:int, message:string, errors?:array}|null
     */
    private function decide(Request $request, SupplierCheck $check): ?array
    {
        if (! $check->blocks()) {
            return null;
        }

        if (! $request->boolean('supplier_override')) {
            return ['status' => 422, 'message' => $this->message($check)];
        }

        if (! $request->user()?->can(self::OVERRIDE_PERMISSION)) {
            return ['status' => 403, 'message' => __('supplier_qualification.no_permission')];
        }

        $reason = trim((string) $request->input('supplier_override_reason'));
        if (mb_strlen($reason) < 3) {
            $message = __('supplier_qualification.override_reason_required');

            return ['status' => 422, 'message' => $message, 'errors' => ['supplier_override_reason' => [$message]]];
        }

        $check->overrideReason = mb_substr($reason, 0, 2000);

        return null;
    }

    private function message(SupplierCheck $check): string
    {
        $labels = DB::table('companies')->whereIn('id', array_keys($check->blocked))->pluck('label', 'id');

        $details = collect($check->blocked)
            ->map(fn (array $state, int $id) => ($labels[$id] ?? "#{$id}") . ' : ' . $this->detail($state))
            ->implode(' ; ');

        return __('supplier_qualification.blocked_' . $check->context) . ' ' . $details;
    }

    private function resolve(?object $rating): array
    {
        $state = [
            'status'            => self::UNQUALIFIED,
            'evaluation_status' => $rating?->evaluation_status,
            'approved_at'       => $rating?->approved_at,
            'next_review_at'    => $rating?->next_review_at ? Carbon::parse($rating->next_review_at)->toDateString() : null,
            'evaluated_at'      => $rating?->created_at,
        ];

        if ($rating === null || $rating->evaluation_status !== 'approved') {
            return $state;
        }

        $overdue = $state['next_review_at'] !== null
            && Carbon::parse($state['next_review_at'])->startOfDay()->lt(Carbon::today());

        $state['status'] = $overdue ? self::REVIEW_OVERDUE : self::QUALIFIED;

        return $state;
    }
}
