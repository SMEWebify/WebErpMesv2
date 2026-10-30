// Taux de livraison à l'heure des fournisseurs (App\Services\Purchases\SupplierOnTimeDelivery).

/**
 * Classe du badge d'un taux (en %) : vert à 95 et plus, orange à 85 et plus, rouge dessous.
 * Mêmes seuils que SupplierOnTimeDelivery::badgeClass() (fiche fournisseur).
 */
export function onTimeBadgeClass(rate) {
    if (rate === null || rate === undefined) return 'text-bg-secondary';
    if (rate >= 95) return 'text-bg-success';
    if (rate >= 85) return 'text-bg-warning';
    return 'text-bg-danger';
}

/** « 87,5 % », ou « — » sans ligne mesurée. */
export function formatOnTimeRate(rate, locale = 'fr-FR') {
    if (rate === null || rate === undefined) return '—';
    return `${Number(rate).toLocaleString(locale, { maximumFractionDigits: 1 })} %`;
}
