// Accusé de réception (AR) fournisseur des commandes d'achat
// (App\Services\Purchases\SupplierAcknowledgement).

/** AR saisissable : commande passée, ni en cours (1) ni annulée (5). */
export function canAcknowledge(purchaseStatu) {
    const statu = Number(purchaseStatu);
    return Number.isFinite(statu) && statu !== 1 && statu !== 5;
}

/** Date de référence des retards : confirmée par le fournisseur, sinon demandée. */
export function referenceDate(line) {
    return line?.confirmed_delivery_date || line?.delivery_date || null;
}

function toDay(dateStr) {
    if (!dateStr) return null;
    const [y, m, d] = String(dateStr).slice(0, 10).split('-').map(Number);
    if (!y || !m || !d) return null;
    return Date.UTC(y, m - 1, d);
}

/**
 * Écart en jours entre date confirmée et date demandée (positif = plus tard que
 * demandé), null s'il manque l'une des deux. Même calcul que SupplierAcknowledgement::gapDays().
 */
export function gapDays(requested, confirmed) {
    const a = toDay(requested);
    const b = toDay(confirmed);
    if (a === null || b === null) return null;
    return Math.round((b - a) / 86400000);
}

/** Badge d'un écart : en retard sur la demande = rouge, en avance = vert, nul = rien. */
export function gapBadgeClass(gap) {
    if (gap === null || gap === undefined || gap === 0) return null;
    return gap > 0 ? 'text-bg-danger' : 'text-bg-success';
}

/** « +3 j » / « −2 j ». */
export function formatGap(gap, unit = 'j') {
    if (gap === null || gap === undefined) return '';
    return `${gap > 0 ? '+' : '−'}${Math.abs(gap)} ${unit}`;
}

/**
 * Lignes proposées dans la fenêtre « AR reçu ».
 * - `onlyId` : une seule ligne cochée (action de la ligne) ;
 * - sinon les lignes sans AR sont cochées, ou toutes si toutes en ont déjà un.
 * Date confirmée proposée : celle déjà saisie, sinon la date demandée.
 * Les lignes à quantité nulle ne sont pas proposées.
 */
export function ackDraftLines(lines, onlyId = null) {
    const candidates = (lines ?? []).filter((l) => Number(l.qty) > 0);
    const anyMissing = candidates.some((l) => !l.supplier_ack_at);

    return candidates.map((l) => ({
        id:                      l.id,
        checked:                 onlyId !== null ? l.id === onlyId : (!anyMissing || !l.supplier_ack_at),
        confirmed_delivery_date: (l.confirmed_delivery_date || l.delivery_date || '').slice(0, 10),
    }));
}

/** Corps de la requête d'enregistrement (lignes cochées seulement). */
export function ackPayload(draft, ackAt, reference) {
    return {
        ack_at:    ackAt,
        reference: reference?.trim() ? reference.trim() : null,
        lines:     draft
            .filter((l) => l.checked)
            .map((l) => ({ id: l.id, confirmed_delivery_date: l.confirmed_delivery_date || null })),
    };
}
