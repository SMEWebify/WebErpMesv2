// Revue de contrat d'une commande : règles d'affichage partagées par l'onglet de
// la fiche commande et l'index. Les règles qui comptent sont reprises côté
// serveur (App\Services\Workflow\ContractReview) : ici, on évite seulement un
// aller-retour pour un refus certain.

export const REVIEW_BADGES = {
    done:     { className: 'text-bg-success', icon: 'fa-check-circle' },
    rejected: { className: 'text-bg-danger',  icon: 'fa-times-circle' },
    todo:     { className: 'text-bg-warning', icon: 'fa-hourglass-half' },
};

/** Badge de l'état de revue ; null pour une commande interne (pas de revue). */
export function reviewBadge(status) {
    return REVIEW_BADGES[status] ?? null;
}

/** Points de la configuration actuelle non cochés. */
export function uncheckedItems(checklist = []) {
    return checklist.filter(item => item.configured !== false && !item.checked);
}

function hasGaps(gaps) {
    return typeof gaps === 'string' && gaps.trim() !== '';
}

/**
 * Raison de ne pas pouvoir viser, null si le visa peut partir.
 * Rejeter exige des écarts ; valider avec un point non coché aussi.
 */
export function decisionBlocker(decision, checklist, gaps) {
    if (hasGaps(gaps)) return null;
    if (decision === 'rejected') return 'gaps_required_reject';
    if (decision === 'approved' && uncheckedItems(checklist).length > 0) return 'gaps_required_unchecked';
    return null;
}

/** Corps envoyé au serveur : { review_gaps, review_checklist: { clé: bool } }. */
export function draftPayload(checklist, gaps) {
    return {
        review_gaps: hasGaps(gaps) ? gaps.trim() : null,
        review_checklist: Object.fromEntries(
            checklist.filter(item => item.configured !== false).map(item => [item.key, Boolean(item.checked)]),
        ),
    };
}
