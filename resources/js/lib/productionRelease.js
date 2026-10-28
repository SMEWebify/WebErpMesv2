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
