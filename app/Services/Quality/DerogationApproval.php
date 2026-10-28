<?php

namespace App\Services\Quality;

use App\Models\Quality\QualityDerogation;
use App\Models\User;
use App\Services\Files\FileRole;

/**
 * Règles de validation d'une dérogation (ISO 19443 / ISO 9001 § 8.7).
 *
 * Une dérogation **externe** engage le client : elle ne passe en « Validé » que
 * si sa réponse est prouvée — client identifié, date et signataire de la
 * réponse, texte de la réponse, et une pièce dans la GED (accord client ou
 * document signé, ce que DocuSign y range de lui-même). Une dérogation interne
 * n'exige que la décision.
 *
 * Dans les deux cas, valider ou modifier une dérogation validée demande la
 * permission `quality-derogation-validate`.
 */
class DerogationApproval
{
    /** Rôles de fichier qui prouvent l'accord du client. */
    public const PROOF_ROLES = [FileRole::CUSTOMER_APPROVAL, FileRole::SIGNED];

    public function canValidate(?User $user): bool
    {
        return (bool) $user?->can(QualityDerogation::VALIDATE_PERMISSION);
    }

    /**
     * Ce qui manque pour valider la dérogation, en libellés lisibles. Vide = validable.
     *
     * @return array<int, string>
     */
    public function missingForValidation(QualityDerogation $derogation): array
    {
        $missing = [];

        if (blank($derogation->decision)) {
            $missing[] = __('derogations.missing_decision');
        }

        if (! $derogation->isExternal()) {
            return $missing;
        }

        if (! $derogation->companies_id) {
            $missing[] = __('derogations.missing_company');
        }
        if (blank($derogation->reply)) {
            $missing[] = __('derogations.missing_reply');
        }
        if (! $derogation->customer_reply_date) {
            $missing[] = __('derogations.missing_reply_date');
        }
        if (blank($derogation->customer_signatory)) {
            $missing[] = __('derogations.missing_signatory');
        }
        if (! $this->hasProof($derogation)) {
            $missing[] = __('derogations.missing_proof');
        }

        return $missing;
    }

    public function hasProof(QualityDerogation $derogation): bool
    {
        return $derogation->exists
            && $derogation->files()->wherePivotIn('role', self::PROOF_ROLES)->exists();
    }

    /**
     * Pose l'auteur et la date de validation quand la dérogation entre en
     * « Validé », les efface si elle en sort.
     */
    public function stamp(QualityDerogation $derogation, ?User $user): void
    {
        if ($derogation->isValidated()) {
            if (! $derogation->validated_at || $derogation->isDirty('statu')) {
                $derogation->validated_by = $user?->id;
                $derogation->validated_at = now();
            }

            return;
        }

        $derogation->validated_by = null;
        $derogation->validated_at = null;
    }
}
