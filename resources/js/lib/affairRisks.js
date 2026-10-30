/**
 * Registre des risques d'une affaire : règles partagées par l'onglet « Risques »
 * et la carte de la synthèse. Le serveur (AffairRiskRegister) reste seul juge ;
 * ces fonctions ne servent qu'à l'affichage et à prévenir avant l'envoi.
 */

export const STATUSES = ['open', 'mitigated', 'closed', 'occurred'];
export const ACTIVE = ['open', 'occurred'];
export const SCALE = [1, 2, 3, 4];

export function criticality(probability, severity) {
    return (Number(probability) || 0) * (Number(severity) || 0);
}

export function isActive(status) {
    return ACTIVE.includes(status);
}

export function isCritical(risk, threshold) {
    if (!risk) return false;
    return criticality(risk.probability, risk.severity) >= Number(threshold);
}

/** Échéance passée sur un risque encore à traiter (dates 'Y-m-d'). */
export function isOverdue(risk, todayIso) {
    if (!risk || !isActive(risk.status) || !risk.due_date) return false;
    return String(risk.due_date).slice(0, 10) < todayIso;
}

/** Badge de criticité : rouge au seuil, orange à partir de la moitié, vert en dessous. */
export function criticalityClass(value, threshold) {
    const t = Number(threshold) || 1;
    if (value >= t) return 'text-bg-danger';
    if (value >= Math.ceil(t / 2)) return 'text-bg-warning';
    return 'text-bg-success';
}

export function statusClass(status) {
    return {
        open: 'text-bg-primary',
        occurred: 'text-bg-danger',
        mitigated: 'text-bg-info',
        closed: 'text-bg-success',
    }[status] ?? 'text-bg-secondary';
}

/**
 * La saisie clôture-t-elle un risque critique (avant ou après modification) ?
 * Même règle que le serveur : baisser la gravité dans la même saisie ne dispense pas.
 */
export function closeNeedsPermission(original, draft, threshold) {
    if (!draft || draft.status !== 'closed' || original?.status === 'closed') return false;
    return isCritical(original, threshold) || isCritical(draft, threshold);
}

export function emptyDraft(categories = []) {
    return {
        label: '',
        description: '',
        category: categories[0] ?? '',
        probability: 2,
        severity: 2,
        mitigation: '',
        owner_id: '',
        due_date: '',
        status: 'open',
        orders_id: '',
    };
}

export function draftFrom(risk) {
    return {
        label: risk.label ?? '',
        description: risk.description ?? '',
        category: risk.category ?? '',
        probability: risk.probability ?? 2,
        severity: risk.severity ?? 2,
        mitigation: risk.mitigation ?? '',
        owner_id: risk.owner_id ?? '',
        due_date: risk.due_date ?? '',
        status: risk.status ?? 'open',
        orders_id: risk.orders_id ?? '',
    };
}

/** Corps de la requête : champs vides envoyés à null. */
export function riskPayload(draft) {
    const orNull = (v) => (v === '' || v === undefined ? null : v);
    return {
        label: String(draft.label ?? '').trim(),
        description: orNull(draft.description),
        category: draft.category,
        probability: Number(draft.probability),
        severity: Number(draft.severity),
        mitigation: orNull(draft.mitigation),
        owner_id: orNull(draft.owner_id) === null ? null : Number(draft.owner_id),
        due_date: orNull(draft.due_date),
        status: draft.status,
        orders_id: orNull(draft.orders_id) === null ? null : Number(draft.orders_id),
    };
}
