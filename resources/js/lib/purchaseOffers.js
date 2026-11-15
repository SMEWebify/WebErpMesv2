/**
 * Offres fournisseurs indicées des demandes de prix (PurchaseOfferRevisions).
 */

/**
 * Libellé d'une offre : « Réf · ind. B », la référence ou l'indice seul, sinon
 * « sans indice ».
 */
export function offerLabel(offer, trans = {}) {
    const reference = (offer?.reference ?? '').toString().trim();
    const revision = (offer?.revision ?? '').toString().trim();
    const parts = [];
    if (reference) parts.push(reference);
    if (revision) parts.push(`${trans.revision_short ?? 'ind.'} ${revision}`);

    return parts.length ? parts.join(' · ') : (trans.no_revision ?? '');
}

/**
 * Demande de prix encore modifiable et commandable : ni remplacée par un indice
 * suivant, ni sortie de « en cours » pour les lignes.
 */
export function quotationLocks(quotation) {
    const superseded = !!quotation?.superseded;

    return {
        superseded,
        canEditLines: !superseded && quotation?.statu === 1,
        canOrder: !superseded && quotation?.statu !== 6,
        canRevise: !quotation?.revision_blocked,
    };
}
