// Dossier de fin de fabrication d'une ligne de commande :
// { required, validated_at, validated_by, source } (ISO, nom, manual|siteflow).

/** 'none' (non exigé), 'pending' (exigé, à valider) ou 'validated'. */
export function dossierState(dossier) {
    if (dossier?.validated_at) return 'validated';
    if (dossier?.required) return 'pending';
    return 'none';
}

/**
 * Texte « Dossier validé le :date par :user ». Sans auteur (validation par
 * Siteflow, compte supprimé), la mention « par » disparaît.
 */
export function dossierValidatedText(template, dossier, locale = 'fr-FR') {
    if (!dossier?.validated_at) return '';

    const date = new Date(dossier.validated_at);
    const formatted = Number.isNaN(date.getTime())
        ? dossier.validated_at
        : date.toLocaleDateString(locale);

    let text = String(template ?? ':date').replace(':date', formatted);
    if (dossier.validated_by) {
        text = text.replace(':user', dossier.validated_by);
    } else {
        text = text.replace(/\s*\S+\s+:user/, '');
    }

    return text;
}

/**
 * Lignes cochées concernées par une action : articles seulement (une section
 * n'a pas de dossier), et seulement celles que l'action change.
 */
export function dossierActionIds(lines, selected, action) {
    const chosen = selected instanceof Set ? selected : new Set(selected ?? []);
    const keep = {
        validate: (l) => dossierState(l.dossier) !== 'validated',
        revoke:   (l) => dossierState(l.dossier) === 'validated',
        require:  (l) => !l.dossier?.required,
        waive:    (l) => Boolean(l.dossier?.required),
    }[action] ?? (() => false);

    return (lines ?? [])
        .filter((l) => chosen.has(l.id))
        .filter((l) => (l.line_type ?? 'article') === 'article')
        .filter(keep)
        .map((l) => l.id);
}
