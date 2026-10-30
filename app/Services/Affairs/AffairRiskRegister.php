<?php

namespace App\Services\Affairs;

use App\Models\User;
use App\Models\Workflow\AffairCategory;
use App\Models\Workflow\AffairRisk;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Registre des risques d'une affaire (opportunité), suivi jusqu'à la livraison.
 *
 * Criticité = probabilité × gravité (1-4 chacune). Un risque est « actif » tant
 * qu'il est ouvert ou survenu ; actif et à criticité ≥ seuil (config/affairs.php),
 * il est critique : signalé dans la synthèse, et sa clôture est réservée à
 * `affair-risk-close`. La criticité est prise avant ET après la modification,
 * pour qu'on ne clôture pas en baissant la gravité dans la même saisie.
 *
 * Saisie : module opportunités (middleware de route). Pas de suppression.
 *
 * Revue de contrat : une catégorie d'affaire `requires_risk_register` fait
 * afficher un avertissement tant qu'aucun risque n'est recensé — jamais bloquant.
 */
class AffairRiskRegister
{
    public const PERMISSION = 'affair-risk-close';

    public const OPEN = 'open';
    public const MITIGATED = 'mitigated';
    public const CLOSED = 'closed';
    public const OCCURRED = 'occurred';
    public const STATUSES = [self::OPEN, self::MITIGATED, self::CLOSED, self::OCCURRED];

    /** Risques encore à traiter : comptent dans les blocages et la carte de la synthèse. */
    public const ACTIVE = [self::OPEN, self::OCCURRED];

    public const SCALE_MIN = 1;
    public const SCALE_MAX = 4;

    /** @return string[] */
    public static function categories(): array
    {
        return array_values(array_filter((array) config('affairs.risks.categories', []), 'is_string'));
    }

    public static function threshold(): int
    {
        return max(1, (int) config('affairs.risks.critical_threshold', 9));
    }

    public static function criticality($probability, $severity): int
    {
        return (int) $probability * (int) $severity;
    }

    public static function isActive(?string $status): bool
    {
        return in_array($status, self::ACTIVE, true);
    }

    public static function isCritical($probability, $severity): bool
    {
        return self::criticality($probability, $severity) >= self::threshold();
    }

    public static function isOverdue(?string $status, $dueDate, CarbonInterface $today): bool
    {
        return self::isActive($status) && $dueDate && Carbon::parse($dueDate)->startOfDay()->lt($today->copy()->startOfDay());
    }

    public function canClose(?User $user): bool
    {
        return (bool) $user?->can(self::PERMISSION);
    }

    /**
     * Crée ou modifie un risque de l'affaire.
     *
     * @param  array<string, mixed>  $data  données validées
     *
     * @throws AffairRiskException
     */
    public function save(Opportunities $opportunity, ?AffairRisk $risk, array $data, User $user): AffairRisk
    {
        return DB::transaction(function () use ($opportunity, $risk, $data, $user) {
            if ($risk !== null) {
                $risk = AffairRisk::whereKey($risk->getKey())->lockForUpdate()->firstOrFail();
                if ((int) $risk->opportunities_id !== (int) $opportunity->id) {
                    throw new AffairRiskException(__('affairs.risks.errors.not_found'), 404);
                }
            }

            if (! empty($data['orders_id'])
                && ! in_array((int) $data['orders_id'], (new AffairScope($opportunity))->orderIds(), true)) {
                throw new AffairRiskException(__('affairs.risks.errors.order_outside_affair'), 422);
            }

            $previousStatus = $risk?->status;
            $wasCritical = $risk !== null && self::isCritical($risk->probability, $risk->severity);

            $risk ??= new AffairRisk(['opportunities_id' => $opportunity->id]);
            $risk->fill(collect($data)->only([
                'orders_id', 'label', 'description', 'category', 'probability', 'severity',
                'mitigation', 'owner_id', 'due_date', 'status',
            ])->all());
            $risk->status ??= self::OPEN;

            $closing = $risk->status === self::CLOSED && $previousStatus !== self::CLOSED;
            if ($closing && ($wasCritical || self::isCritical($risk->probability, $risk->severity)) && ! $this->canClose($user)) {
                throw new AffairRiskException(__('affairs.risks.errors.close_forbidden'), 403);
            }

            if ($closing) {
                $risk->closed_by = $user->id;
                $risk->closed_at = now();
            } elseif ($risk->status !== self::CLOSED) {
                $risk->closed_by = null;
                $risk->closed_at = null;
            }
            if (! $risk->exists) {
                $risk->created_by = $user->id;
            }

            $risk->save();

            return $risk;
        });
    }

    /**
     * Registre de l'affaire pour l'onglet « Risques » (2 requêtes : risques, puis
     * commandes de l'affaire pour la liste de choix).
     *
     * @return array<string, mixed>
     */
    public function register(Opportunities $opportunity, ?User $viewer, ?CarbonInterface $today = null): array
    {
        $today ??= now();

        $rows = AffairRisk::query()
            ->leftJoin('users as owner', 'owner.id', '=', 'affair_risks.owner_id')
            ->leftJoin('orders', 'orders.id', '=', 'affair_risks.orders_id')
            ->where('affair_risks.opportunities_id', $opportunity->id)
            ->orderBy('affair_risks.id')
            ->get(['affair_risks.*', 'owner.name as owner_name', 'orders.code as order_code'])
            ->map(fn (AffairRisk $risk) => [
                'id'          => $risk->id,
                'label'       => $risk->label,
                'description' => $risk->description,
                'category'    => $risk->category,
                'probability' => $risk->probability,
                'severity'    => $risk->severity,
                'criticality' => $risk->criticality,
                'critical'    => self::isCritical($risk->probability, $risk->severity),
                'mitigation'  => $risk->mitigation,
                'owner_id'    => $risk->owner_id,
                'owner_name'  => $risk->owner_name,
                'due_date'    => $risk->due_date?->format('Y-m-d'),
                'overdue'     => self::isOverdue($risk->status, $risk->due_date, $today),
                'status'      => $risk->status,
                'orders_id'   => $risk->orders_id,
                'order_code'  => $risk->order_code,
                'closed_at'   => $risk->closed_at?->format('Y-m-d H:i'),
            ])
            ->values()
            ->all();

        $orderIds = (new AffairScope($opportunity))->orderIds();

        return [
            'data' => $rows,
            'meta' => [
                'categories' => self::categories(),
                'statuses'   => self::STATUSES,
                'threshold'  => self::threshold(),
                'can_close'  => $this->canClose($viewer),
                'orders'     => $orderIds === [] ? [] : Orders::whereIn('id', $orderIds)->orderBy('id')
                    ->get(['id', 'code'])->map(fn ($o) => ['id' => $o->id, 'code' => $o->code])->all(),
            ],
        ];
    }

    /**
     * Avertissement de la revue de contrat : la catégorie d'affaire de la commande
     * exige un registre des risques et aucun risque n'est recensé (sur l'affaire
     * ou sur la commande). Null sinon — en particulier quand l'option est inactive.
     *
     * @return array{opportunity_url: ?string}|null
     */
    public function contractReviewWarning(Orders $order): ?array
    {
        if (! $order->affair_category_id
            || ! AffairCategory::whereKey($order->affair_category_id)->value('requires_risk_register')) {
            return null;
        }

        $opportunityId = $order->quotes_id
            ? Quotes::withTemplates()->whereKey($order->quotes_id)->value('opportunities_id')
            : null;

        $hasRisk = AffairRisk::query()
            ->where(fn ($q) => $q->where('orders_id', $order->id)
                ->when($opportunityId, fn ($q) => $q->orWhere('opportunities_id', $opportunityId)))
            ->exists();

        if ($hasRisk) {
            return null;
        }

        return [
            'opportunity_url' => $opportunityId ? route('opportunities.show', $opportunityId) . '#Risks' : null,
        ];
    }
}
