<?php

namespace App\Services\Purchases;

use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Notifications\NonConformityNotification;
use App\Services\DocumentCodeGenerator;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Contrôle documentaire d'une ligne de réception par la qualité, à côté du contrôle
 * physique. Compare le certificat reçu (received_certificate_type) au type exigé sur la
 * ligne d'achat (purchase_lines.certificate_type), puis enregistre le visa.
 *
 * Seul écrivain des colonnes document_check_*. Ne touche jamais au stock : la mise en
 * stock reste indépendante de la décision (quarantaine = chantier séparé).
 */
class ReceiptDocumentCheck
{
    public const PENDING       = 'pending';
    public const COMPLIANT     = 'compliant';
    public const NON_COMPLIANT = 'non_compliant';
    public const RESULTS       = [self::PENDING, self::COMPLIANT, self::NON_COMPLIANT];

    // Statut documentaire d'une ligne sans exigence ni certificat reçu
    public const NOT_REQUIRED = 'not_required';

    // Décision finale (physique ET documentaire)
    public const DECISION_ACCEPTED = 'accepted';
    public const DECISION_REJECTED = 'rejected';
    public const DECISION_PENDING  = 'pending';

    // Écarts de certificat
    public const GAP_MISSING      = 'missing';
    public const GAP_INSUFFICIENT = 'insufficient';
    public const GAP_DIFFERENT    = 'different';

    public function __construct(
        private NotificationService $notifications,
        private DocumentCodeGenerator $codes,
    ) {
    }

    public static function requiredCertificate(PurchaseReceiptLines $line): ?string
    {
        $value = $line->purchaseLines?->certificate_type;

        return $value === '' ? null : $value;
    }

    /** Une ligne est soumise au visa si un certificat est exigé ou a été reçu. */
    public static function isConcerned(PurchaseReceiptLines $line): bool
    {
        return self::requiredCertificate($line) !== null
            || ($line->received_certificate_type ?? '') !== ''
            || $line->document_check_result !== null;
    }

    /**
     * Écart entre le certificat exigé et le certificat reçu, ou null.
     * Rang EN 10204 = ordre de config('purchase_quality.certificate_types') : un
     * certificat de rang supérieur couvre l'exigence (3.1 reçu pour 2.2 exigé).
     *
     * @return array{code: string, required: ?string, received: ?string}|null
     */
    public static function certificateGap(?string $required, ?string $received): ?array
    {
        $required = $required === '' ? null : $required;
        $received = $received === '' ? null : $received;

        if ($required === null) {
            return null;
        }

        if ($received === null) {
            return ['code' => self::GAP_MISSING, 'required' => $required, 'received' => null];
        }

        if ($received === $required) {
            return null;
        }

        $ranks        = array_flip(array_values((array) config('purchase_quality.certificate_types', [])));
        $requiredRank = $ranks[$required] ?? null;
        $receivedRank = $ranks[$received] ?? null;

        if ($requiredRank === null || $receivedRank === null) {
            return ['code' => self::GAP_DIFFERENT, 'required' => $required, 'received' => $received];
        }

        return $receivedRank < $requiredRank
            ? ['code' => self::GAP_INSUFFICIENT, 'required' => $required, 'received' => $received]
            : null;
    }

    public static function gapFor(PurchaseReceiptLines $line): ?array
    {
        return self::certificateGap(self::requiredCertificate($line), $line->received_certificate_type);
    }

    /** not_required | pending | compliant | non_compliant */
    public static function documentStatus(PurchaseReceiptLines $line): string
    {
        if (in_array($line->document_check_result, [self::COMPLIANT, self::NON_COMPLIANT], true)) {
            return $line->document_check_result;
        }

        return self::isConcerned($line) ? self::PENDING : self::NOT_REQUIRED;
    }

    /**
     * Contrôle physique déduit des champs existants : une pièce rejetée = non conforme,
     * une inspection datée ou des pièces acceptées = conforme, sinon en attente.
     */
    public static function physicalStatus(PurchaseReceiptLines $line): string
    {
        if ((float) $line->rejected_qty > 0) {
            return self::NON_COMPLIANT;
        }

        if ((float) $line->accepted_qty > 0 || $line->inspection_date !== null) {
            return self::COMPLIANT;
        }

        return self::PENDING;
    }

    /** Décision finale = physique ET documentaire conformes. */
    public static function decision(PurchaseReceiptLines $line): string
    {
        $physical = self::physicalStatus($line);
        $document = self::documentStatus($line);

        if ($physical === self::NON_COMPLIANT || $document === self::NON_COMPLIANT) {
            return self::DECISION_REJECTED;
        }

        if ($physical === self::COMPLIANT && in_array($document, [self::COMPLIANT, self::NOT_REQUIRED], true)) {
            return self::DECISION_ACCEPTED;
        }

        return self::DECISION_PENDING;
    }

    /**
     * Lignes en attente de visa documentaire : certificat exigé ou reçu, pas encore
     * visées ou visées « en attente ».
     */
    public static function awaitingQuery(): Builder
    {
        return PurchaseReceiptLines::query()
            ->join('purchase_lines', 'purchase_lines.id', '=', 'purchase_receipt_lines.purchase_line_id')
            ->where(function (Builder $q) {
                $q->whereNull('purchase_receipt_lines.document_check_result')
                  ->orWhere('purchase_receipt_lines.document_check_result', self::PENDING);
            })
            ->where(function (Builder $q) {
                $q->where(fn (Builder $w) => $w->whereNotNull('purchase_lines.certificate_type')->where('purchase_lines.certificate_type', '!=', ''))
                  ->orWhere(fn (Builder $w) => $w->whereNotNull('purchase_receipt_lines.received_certificate_type')->where('purchase_receipt_lines.received_certificate_type', '!=', ''));
            })
            ->select('purchase_receipt_lines.*');
    }

    /**
     * Enregistre le visa. Un « conforme » malgré un écart de certificat exige un
     * commentaire (dérogation, certificat attendu…). Le certificat reçu peut être
     * corrigé dans le même geste. Un non-conforme peut créer une NC fournisseur
     * (lien quality_non_conformity_id ; une NC déjà liée est conservée).
     *
     * @throws ValidationException
     */
    public function record(
        PurchaseReceiptLines $line,
        User $user,
        string $result,
        ?string $comment,
        bool $createNonConformity = false,
        bool $updateReceived = false,
        ?string $receivedCertificate = null,
    ): PurchaseReceiptLines {
        if (! in_array($result, self::RESULTS, true)) {
            throw ValidationException::withMessages(['document_check_result' => __('validation.in', ['attribute' => 'document_check_result'])]);
        }

        $comment = trim((string) $comment) === '' ? null : trim((string) $comment);

        if ($updateReceived) {
            $line->received_certificate_type = $receivedCertificate === '' ? null : $receivedCertificate;
        }

        $line->loadMissing('purchaseLines.purchase', 'purchaseLines.tasks.OrderLines');

        if ($result === self::COMPLIANT && self::gapFor($line) !== null && $comment === null) {
            throw ValidationException::withMessages([
                'document_check_comment' => __('receipt_document_check.comment_required_with_gap'),
            ]);
        }

        return DB::transaction(function () use ($line, $user, $result, $comment, $createNonConformity) {
            if ($result === self::NON_COMPLIANT && $createNonConformity && ! $line->quality_non_conformity_id) {
                $line->quality_non_conformity_id = $this->createNonConformity($line, $user, $comment)->id;
            }

            $line->forceFill([
                'document_check_by'      => $user->id,
                'document_check_at'      => now(),
                'document_check_result'  => $result,
                'document_check_comment' => $comment,
            ])->save();

            return $line;
        });
    }

    /**
     * Le certificat reçu a changé après un visa : le visa ne porte plus sur ce document,
     * il est effacé (la ligne revient dans la liste d'attente).
     */
    public function receivedCertificateChanged(PurchaseReceiptLines $line, ?string $received): void
    {
        $received = $received === '' ? null : $received;

        if ($received === $line->received_certificate_type) {
            return;
        }

        $line->received_certificate_type = $received;

        if ($line->document_check_result !== null) {
            $line->forceFill([
                'document_check_by'      => null,
                'document_check_at'      => null,
                'document_check_result'  => null,
                'document_check_comment' => null,
            ]);
        }
    }

    private function createNonConformity(PurchaseReceiptLines $line, User $user, ?string $comment): QualityNonConformity
    {
        $last = QualityNonConformity::latest('id')->first();
        $code = $this->codes->generateDocumentCode('non-conformities', $last?->id ?? 0);

        $gap   = self::gapFor($line);
        $label = __('receipt_document_check.nc_label', ['line' => $line->purchaseLines?->label ?? '#' . $line->id]);

        $failure = $gap
            ? __('receipt_document_check.gap_' . $gap['code'], ['required' => $gap['required'] ?? '—', 'received' => $gap['received'] ?? '—'])
            : null;
        $failure = trim(implode("\n", array_filter([$failure, $comment])));

        // NC externe (fournisseur) : type 2, comme QualityNonConformityService::createNCFromDelivery()
        $data = [
            'code'        => $code,
            'type'        => 2,
            'label'       => mb_substr($label, 0, 255),
            'statu'       => 1,
            'user_id'     => $user->id,
            'companie_id' => $line->purchaseLines?->purchase?->companies_id,
            'qty'         => $line->receipt_qty,
        ];

        if ($failure !== '') {
            $data['failure_comment'] = $failure;
        }

        if ($orderLine = $line->purchaseLines?->tasks?->OrderLines) {
            $data['order_lines_id'] = $orderLine->id;
        }

        $nonConformity = QualityNonConformity::create($data);
        $this->notifications->sendNotification(NonConformityNotification::class, $nonConformity, 'non_conformity_notification');

        return $nonConformity;
    }
}
