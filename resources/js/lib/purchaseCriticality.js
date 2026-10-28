// Criticité d'une ligne d'achat (certificat EN 10204, code de construction, niveau) :
// reprise de l'article choisi, puis modifiable. Le serveur fait de même pour les
// lignes créées hors écran (PurchaseLines::booted).

export const CRITICALITY_FIELDS = ['certificate_type', 'construction_code', 'nuclear_level'];

// Article choisi : sa criticité remplace celle du formulaire, y compris vide
// (changer d'article ne doit pas garder l'exigence de l'article précédent).
export function applyProductCriticality(form, product) {
    const next = { ...form };
    CRITICALITY_FIELDS.forEach((field) => {
        next[field] = product?.[field] ?? '';
    });
    return next;
}

// Valeurs proposées : celles de la configuration, plus la valeur déjà portée si
// elle en a été retirée depuis (sinon le select l'effacerait sans le dire).
export function criticalityChoices(options, current) {
    const values = [...(options ?? [])];
    if (current && !values.includes(current)) values.push(current);
    return values;
}

// Badges affichés sous la désignation : seulement les champs renseignés.
export function criticalityBadges(line) {
    return CRITICALITY_FIELDS
        .map((field) => line?.[field])
        .filter((value) => value !== null && value !== undefined && value !== '');
}
