// Répertoire Sirene (CompanyRegistryLookup côté serveur) : reprise d'un
// résultat dans un formulaire de société.

// Champs de la société repris d'un résultat (l'adresse est à part).
export const REGISTRY_FIELDS = ['label', 'siren', 'naf_code', 'intra_community_vat'];

// SIRET (14 chiffres) affiché par groupes : 123 456 789 00012.
export function formatSiret(siret) {
    const s = String(siret ?? '');
    if (!/^\d{14}$/.test(s)) return s;
    return `${s.slice(0, 3)} ${s.slice(3, 6)} ${s.slice(6, 9)} ${s.slice(9)}`;
}

// Une ligne d'adresse lisible : « 8 RUE DE LONDRES, 75009 PARIS ».
export function formatAddress(address) {
    if (!address) return '';
    const town = [address.zipcode, address.city].filter(Boolean).join(' ');
    return [address.adress, town].filter(Boolean).join(', ');
}

// Nouveau formulaire avec les champs du résultat. Un champ vide du résultat
// n'efface jamais une saisie.
export function applyRegistryResult(form, result) {
    const next = { ...form };
    REGISTRY_FIELDS.forEach(field => {
        const value = result?.[field];
        if (value !== null && value !== undefined && value !== '') next[field] = value;
    });
    return next;
}

// Adresse à créer avec la société : seulement si la voie, le code postal et
// la commune sont connus (champs obligatoires d'une adresse).
export function registryAddress(result) {
    const a = result?.address;
    if (!a || !a.adress || !a.zipcode || !a.city) return null;
    return { adress: a.adress, zipcode: a.zipcode, city: a.city, country: a.country || 'France' };
}
