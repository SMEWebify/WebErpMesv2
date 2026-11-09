/**
 * Aperçus calculés par le serveur (tableurs, e-mails) : lecture de la route
 * files.preview et mise en forme pour SheetViewer / MailViewer.
 */

/** Lettre de colonne Excel d'un index commençant à 0 : 0 → A, 25 → Z, 26 → AA. */
export function columnLetter(index) {
    let n = index + 1;
    let letters = '';
    while (n > 0) {
        const rest = (n - 1) % 26;
        letters = String.fromCharCode(65 + rest) + letters;
        n = Math.floor((n - 1) / 26);
    }
    return letters;
}

/** Remplace les :clés d'un texte traduit. */
export function fillPlaceholders(text, values) {
    return Object.entries(values).reduce(
        (result, [key, value]) => result.replaceAll(`:${key}`, String(value)),
        text ?? '',
    );
}

/** Nombre de colonnes à dessiner : la plus longue ligne de la zone lue. */
export function columnCount(rows) {
    return (rows ?? []).reduce((max, row) => Math.max(max, row.length), 0);
}

export function formatSize(bytes, lang = globalThis.document?.documentElement?.lang ?? 'fr') {
    if (!Number.isFinite(bytes) || bytes < 0) return '';
    const [b, kb, mb] = String(lang).startsWith('fr') ? ['o', 'Ko', 'Mo'] : ['B', 'KB', 'MB'];
    if (bytes < 1024) return `${bytes} ${b}`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} ${kb}`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} ${mb}`;
}

/**
 * Document affiché dans l'iframe isolée du lecteur de mail. Le HTML a déjà été
 * nettoyé par le serveur ; la CSP ne laisse en plus passer aucun script ni
 * aucune ressource distante, et les liens s'ouvrent dans un nouvel onglet.
 * Fond blanc et texte noir assumés : c'est le rendu qu'attend l'expéditeur,
 * comme dans une messagerie, y compris en mode sombre.
 */
export function mailDocument(html) {
    return '<!DOCTYPE html><html><head><meta charset="utf-8">'
        + '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src data:; style-src \'unsafe-inline\'; font-src data:">'
        + '<base target="_blank">'
        + '<style>body{margin:12px;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;font-size:14px;background:#fff;color:#000;word-wrap:break-word}img{max-width:100%;height:auto}</style>'
        + `</head><body>${html ?? ''}</body></html>`;
}

/**
 * Lit l'aperçu ; en cas de refus, `reason` vaut unreadable | too_large | missing.
 */
export async function fetchPreview(url, { signal } = {}) {
    const res = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal,
    });
    const payload = await res.json().catch(() => ({}));

    if (!res.ok) {
        const error = new Error(payload?.message || res.statusText);
        error.reason = payload?.reason ?? 'unreadable';
        throw error;
    }

    return payload;
}

/** Clé de traduction du message d'échec. */
export function previewErrorKey(reason) {
    return {
        too_large: 'preview_too_large',
        missing: 'preview_missing',
        not_a_mail: 'preview_not_a_mail',
    }[reason] ?? 'preview_unreadable';
}
