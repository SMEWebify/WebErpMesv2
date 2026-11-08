// Impact d'une non-conformité : mêmes règles que App\Services\Quality\NonConformityImpact,
// pour l'affichage en direct pendant la saisie (le serveur reste la référence).

/** Nombre saisi (virgule décimale admise), null si vide ou invalide. */
export function toNumber(value) {
    if (value === null || value === undefined || value === '') return null;
    const n = Number(String(value).replace(',', '.').trim());
    return Number.isFinite(n) ? n : null;
}

const round2 = (n) => Math.round(n * 100) / 100;

/**
 * Total d'une NC : coût interne saisi, à défaut le réel des retouches, + externe + rebut.
 */
export function impactTotal({ internal, external, scrap, internalActual }) {
    const entered = toNumber(internal);
    const used = entered ?? (toNumber(internalActual) ?? 0);
    return round2(used + (toNumber(external) ?? 0) + (toNumber(scrap) ?? 0));
}

/** Vrai quand le total retient le coût réel faute de coût interne saisi. */
export function usesActualInternal(internal) {
    return toNumber(internal) === null;
}

/**
 * Date de livraison proposée (AAAA-MM-JJ) : date de la ligne + délai, null sans
 * date ou sans délai positif. Calcul en UTC pour ne pas glisser d'un jour.
 */
export function proposedDeliveryDate(dateIso, delayDays) {
    const days = parseInt(delayDays, 10);
    if (!dateIso || !Number.isFinite(days) || days <= 0) return null;
    const [y, m, d] = String(dateIso).slice(0, 10).split('-').map(Number);
    if (!y || !m || !d) return null;
    const date = new Date(Date.UTC(y, m - 1, d + days));
    return date.toISOString().slice(0, 10);
}

/** Opérations de l'ordre de retouche prêtes à envoyer : poste choisi, temps ≥ 0. */
export function reworkPayload(operations) {
    return (operations ?? [])
        .filter((op) => op && op.methods_services_id)
        .map((op) => ({
            methods_services_id: Number(op.methods_services_id),
            hours: Math.max(0, toNumber(op.hours) ?? 0),
            label: (op.label ?? '').trim() || null,
        }));
}
