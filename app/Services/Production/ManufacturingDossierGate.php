<?php

namespace App\Services\Production;

use App\Enums\SalesLineType;
use App\Models\User;
use App\Models\Workflow\AffairCategory;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Dossier de fin de fabrication : une ligne de commande qui l'exige ne passe ni
 * sur un BL ni sur une facture tant qu'il n'est pas validé.
 *
 * - **Exigence** : `order_lines.dossier_required`, reprise à la création de la
 *   ligne de la catégorie d'affaire de la commande
 *   (`affair_categories.requires_manufacturing_dossier`, OrderLinesObserver),
 *   puis réglable ligne par ligne. Une ligne de présentation n'exige jamais rien.
 * - **Validation** : manuelle (permission `dossier-validate`) en attendant le
 *   connecteur Siteflow, qui appellera `validate()` avec la source `siteflow`.
 * - **Contrôle** : appelé à la création de chaque BL et de chaque facture, sur
 *   toutes les portes (BL depuis la commande ou l'écran « Demande de BL »,
 *   facturation directe et son BL automatique, facture depuis des lignes de BL,
 *   génération groupée, facture d'un BL, conversion d'une proforma en facture).
 *   La proforma elle-même n'est pas contrôlée : elle se paie avant fabrication.
 *
 * Une ligne déjà livrée avant d'exiger le dossier reste bloquée à la facturation :
 * l'exigence vaut pour le document à venir, quel que soit l'historique.
 */
class ManufacturingDossierGate
{
    public const PERMISSION = 'dossier-validate';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SITEFLOW = 'siteflow';
    public const SOURCES = [self::SOURCE_MANUAL, self::SOURCE_SITEFLOW];

    /**
     * Exigence d'une ligne neuve : celle de la catégorie d'affaire de sa commande.
     */
    public static function requiredByDefault(?int $orderId): bool
    {
        if (! $orderId) {
            return false;
        }

        $categoryId = Orders::whereKey($orderId)->value('affair_category_id');

        return $categoryId
            && (bool) AffairCategory::whereKey($categoryId)->value('requires_manufacturing_dossier');
    }

    public function isValidated(OrderLines $line): bool
    {
        return $line->dossier_validated_at !== null;
    }

    public function isBlocking(OrderLines $line): bool
    {
        return (bool) $line->dossier_required && ! $this->isValidated($line);
    }

    /**
     * Lignes de commande, parmi celles données, qui exigent un dossier non validé.
     * Une requête, quel que soit le nombre de lignes.
     *
     * @param  iterable<int|string|null>  $orderLineIds
     * @return Collection<int, array{id: int, label: string}>
     */
    public function blockingLines(iterable $orderLineIds): Collection
    {
        $ids = collect($orderLineIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return OrderLines::query()
            ->leftJoin('orders', 'orders.id', '=', 'order_lines.orders_id')
            ->whereIn('order_lines.id', $ids->all())
            ->where('order_lines.dossier_required', true)
            ->whereNull('order_lines.dossier_validated_at')
            ->where(fn ($q) => $q->whereNull('order_lines.line_type')
                ->orWhere('order_lines.line_type', SalesLineType::Article->value))
            ->orderBy('order_lines.orders_id')
            ->orderBy('order_lines.ordre')
            ->toBase()
            ->get(['order_lines.id', 'order_lines.code', 'order_lines.label', 'orders.code as order_code'])
            ->map(fn ($l) => [
                'id'    => (int) $l->id,
                'label' => self::lineLabel($l->order_code, $l->code, $l->label, (int) $l->id),
            ]);
    }

    /**
     * Même contrôle à partir de lignes de BL (facture créée depuis un BL).
     *
     * @param  iterable<int|string>  $deliveryLineIds
     * @return Collection<int, array{id: int, label: string}>
     */
    public function blockingLinesOfDeliveryLines(iterable $deliveryLineIds): Collection
    {
        $ids = collect($deliveryLineIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return $this->blockingLines(DeliveryLines::whereIn('id', $ids->all())->pluck('order_line_id'));
    }

    /**
     * Message de refus, ou null si rien ne bloque.
     *
     * @param  Collection<int, array{id: int, label: string}>  $blocking
     */
    public function refusalMessage(Collection $blocking): ?string
    {
        if ($blocking->isEmpty()) {
            return null;
        }

        return trans_choice('manufacturing_dossier.blocked', $blocking->count(), [
            'lines' => $blocking->pluck('label')->implode(', '),
        ]);
    }

    /**
     * Réponse 422 lisible par tous les écrans de création (`message`, `error`,
     * `errors.lines` selon l'écran), ou null si rien ne bloque.
     *
     * @param  Collection<int, array{id: int, label: string}>  $blocking
     */
    public function jsonRefusal(Collection $blocking): ?JsonResponse
    {
        $message = $this->refusalMessage($blocking);

        if ($message === null) {
            return null;
        }

        return response()->json([
            'message'       => $message,
            'error'         => $message,
            'errors'        => ['lines' => [$message]],
            'dossier_lines' => $blocking->values()->all(),
        ], 422);
    }

    /**
     * Raison de refuser la validation, null si elle peut être donnée.
     */
    public function validationRefusal(OrderLines $line): ?string
    {
        if (! self::isArticle($line)) {
            return __('manufacturing_dossier.not_article');
        }
        if ($this->isValidated($line)) {
            return __('manufacturing_dossier.already_validated');
        }

        return null;
    }

    /**
     * @throws ManufacturingDossierException
     */
    public function validate(OrderLines $line, ?User $user, string $source = self::SOURCE_MANUAL): OrderLines
    {
        if (! in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException("Source de dossier inconnue : {$source}");
        }

        $refusal = $this->validationRefusal($line);

        if ($refusal !== null) {
            throw new ManufacturingDossierException($refusal);
        }

        $line->forceFill([
            'dossier_validated_at' => now(),
            'dossier_validated_by' => $user?->id,
            'dossier_source'       => $source,
        ])->save();

        return $line;
    }

    /**
     * Annule la validation tant que rien n'est parti : une fois la ligne livrée ou
     * facturée, même en partie, le document émis l'a été sur ce dossier.
     *
     * @throws ManufacturingDossierException
     */
    public function revoke(OrderLines $line): OrderLines
    {
        if (! $this->isValidated($line)) {
            throw new ManufacturingDossierException(__('manufacturing_dossier.not_validated_yet'));
        }
        if ((float) $line->delivered_qty > 0 || (float) $line->invoiced_qty > 0) {
            throw new ManufacturingDossierException(__('manufacturing_dossier.already_shipped'));
        }

        $line->forceFill([
            'dossier_validated_at' => null,
            'dossier_validated_by' => null,
            'dossier_source'       => null,
        ])->save();

        return $line;
    }

    /**
     * Exige (ou n'exige plus) le dossier sur une ligne. La validation déjà donnée
     * est conservée : exiger à nouveau ne la fait pas tomber.
     *
     * @throws ManufacturingDossierException
     */
    public function setRequired(OrderLines $line, bool $required): OrderLines
    {
        if ($required && ! self::isArticle($line)) {
            throw new ManufacturingDossierException(__('manufacturing_dossier.not_article'));
        }

        $line->forceFill(['dossier_required' => $required])->save();

        return $line;
    }

    public static function isArticle(OrderLines $line): bool
    {
        return $line->line_type === null || $line->line_type === SalesLineType::Article->value;
    }

    private static function lineLabel(?string $orderCode, ?string $code, ?string $label, int $id): string
    {
        $line = $code ?: ($label ?: '#' . $id);

        return $orderCode ? "{$orderCode} / {$line}" : $line;
    }
}
