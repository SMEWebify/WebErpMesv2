<?php

namespace App\Services\Workflow;

use App\Enums\SalesLineType;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;

/**
 * Prix calculé d'une ligne de commande (`order_lines.use_calculated_price`).
 *
 * Tant que le drapeau est levé, `OrderLines::getSellingPriceAttribute()` relit la
 * gamme à chaque lecture : une modification de la gamme change le prix. Le prix
 * doit être figé à la commande, d'où :
 *
 * - **Activation** libre tant que la commande est en préparation (statut 1 et
 *   revue de contrat non validée) ; au-delà, permission `order-price-recalculate`
 *   et motif tracé par activitylog. Toujours refusée sur une ligne livrée ou
 *   facturée, même en partie, et sur une ligne de présentation.
 * - **Désactivation** toujours permise : le prix calculé du moment est d'abord
 *   écrit dans `selling_price`, sinon la ligne retomberait sur l'ancienne valeur.
 *
 * Les lignes déjà en prix calculé ne sont pas touchées (`wem:orders:calculated-price-lines`).
 */
class OrderLineCalculatedPrice
{
    public const PERMISSION = 'order-price-recalculate';

    public const MIN_REASON_LENGTH = 3;

    /** Statut « Ouvert » d'une commande. */
    public const PREPARATION_STATUS = 1;

    public static function isInPreparation(Orders $order): bool
    {
        return (int) $order->statu === self::PREPARATION_STATUS
            && $order->review_decision !== ContractReview::APPROVED;
    }

    public static function isShipped(OrderLines $line): bool
    {
        return (float) $line->delivered_qty > 0 || (float) $line->invoiced_qty > 0;
    }

    public function canOverride(?User $user): bool
    {
        return (bool) $user?->can(self::PERMISSION);
    }

    /**
     * Raison absolue de refuser l'activation (aucun passe-droit), null sinon.
     */
    public function hardRefusal(OrderLines $line): ?string
    {
        if ($line->line_type !== null && $line->line_type !== SalesLineType::Article->value) {
            return __('calculated_price.errors.not_article');
        }
        if (self::isShipped($line)) {
            return __('calculated_price.errors.shipped');
        }

        return null;
    }

    /**
     * État de l'interrupteur pour l'écran des lignes.
     *
     * @return array{enabled: bool, can_enable: bool, needs_reason: bool, blocked_reason: ?string}
     */
    public function state(OrderLines $line, Orders $order, ?User $viewer): array
    {
        $enabled = (bool) $line->use_calculated_price;
        $hard = $this->hardRefusal($line);
        $locked = ! self::isInPreparation($order);
        $override = $this->canOverride($viewer);

        $blocked = $hard ?? (($locked && ! $override) ? __('calculated_price.errors.order_locked') : null);

        return [
            'enabled' => $enabled,
            'can_enable' => ! $enabled && $blocked === null,
            'needs_reason' => ! $enabled && $blocked === null && $locked,
            'blocked_reason' => $enabled ? null : $blocked,
        ];
    }

    /**
     * @throws OrderLineCalculatedPriceException
     */
    public function enable(OrderLines $line, ?User $user, ?string $reason = null): OrderLines
    {
        return DB::transaction(function () use ($line, $user, $reason) {
            $line = OrderLines::whereKey($line->getKey())->lockForUpdate()->firstOrFail();
            $order = Orders::findOrFail($line->orders_id);

            if ($line->use_calculated_price) {
                return $line;
            }

            $hard = $this->hardRefusal($line);
            if ($hard !== null) {
                throw new OrderLineCalculatedPriceException($hard, 422);
            }

            $override = ! self::isInPreparation($order);
            $reason = is_string($reason) ? trim($reason) : '';

            if ($override) {
                if (! $this->canOverride($user)) {
                    throw new OrderLineCalculatedPriceException(__('calculated_price.errors.order_locked'), 403);
                }
                if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
                    throw new OrderLineCalculatedPriceException(__('calculated_price.errors.reason_required'), 422, true);
                }
            }

            $before = (float) ($line->getRawOriginal('selling_price') ?? 0);
            $line->forceFill(['use_calculated_price' => true])->save();

            activity()
                ->performedOn($line)
                ->causedBy($user)
                ->withProperties([
                    'override' => $override,
                    'reason' => $reason !== '' ? $reason : null,
                    'order_statu' => (int) $order->statu,
                    'review_decision' => $order->review_decision,
                    'stored_price' => $before,
                ])
                ->log('calculated_price_enabled');

            return $line;
        });
    }

    /**
     * Fige le prix calculé du moment puis désactive le calcul.
     */
    public function disable(OrderLines $line, ?User $user): OrderLines
    {
        return DB::transaction(function () use ($line, $user) {
            $line = OrderLines::whereKey($line->getKey())->lockForUpdate()->firstOrFail();

            if (! $line->use_calculated_price) {
                return $line;
            }

            $before = (float) ($line->getRawOriginal('selling_price') ?? 0);
            $computed = round((float) $line->selling_price, 3); // accesseur : prix de la gamme

            $line->forceFill([
                'selling_price' => $computed,
                'use_calculated_price' => false,
            ])->save();

            activity()
                ->performedOn($line)
                ->causedBy($user)
                ->withProperties(['stored_price' => $before, 'frozen_price' => $computed])
                ->log('calculated_price_disabled');

            return $line;
        });
    }
}
