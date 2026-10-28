// Bon pour fabrication d'une ligne de commande : { released_at, released_by } (ISO, nom).

export function isReleased(release) {
    return Boolean(release?.released_at);
}

/**
 * Texte « BPF le :date par :user ». Sans auteur connu (compte supprimé),
 * la mention « par » disparaît plutôt que d'afficher un vide.
 */
export function releasedOnText(template, release, locale = 'fr-FR') {
    if (!isReleased(release)) return '';

    const date = new Date(release.released_at);
    const formatted = Number.isNaN(date.getTime())
        ? release.released_at
        : date.toLocaleDateString(locale);

    let text = String(template ?? ':date').replace(':date', formatted);
    if (release.released_by) {
        text = text.replace(':user', release.released_by);
    } else {
        text = text.replace(/\s*\S+\s+:user/, '');
    }

    return text;
}

/**
 * Lignes cochées à libérer : articles seulement (une section ne se fabrique
 * pas), sans BPF. Les lignes déjà libérées ne sont pas renvoyées au serveur.
 */
export function releasableIds(lines, selected) {
    const chosen = selected instanceof Set ? selected : new Set(selected ?? []);
    return (lines ?? [])
        .filter((l) => chosen.has(l.id))
        .filter((l) => (l.line_type ?? 'article') === 'article')
        .filter((l) => !isReleased(l.release))
        .map((l) => l.id);
}
