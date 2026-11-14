// Écran de scan atelier (docs/pointage-scan.md) : présentation d'un résultat de scan.

// Couleur Bootstrap (text-bg-*) et icône Font Awesome par résultat renvoyé par le serveur.
const RESULT_STYLES = {
    started:          { tone: 'success',   icon: 'fa-play' },
    resumed:          { tone: 'warning',   icon: 'fa-rotate-right' },
    finished:         { tone: 'primary',   icon: 'fa-flag-checkered' },
    needs_qty:        { tone: 'info',      icon: 'fa-keyboard' },
    opened:           { tone: 'secondary', icon: 'fa-up-right-from-square' },
    already_finished: { tone: 'secondary', icon: 'fa-circle-check' },
    ignored:          { tone: 'secondary', icon: 'fa-clone' },
    refused:          { tone: 'danger',    icon: 'fa-ban' },
    not_found:        { tone: 'danger',    icon: 'fa-circle-question' },
    error:            { tone: 'danger',    icon: 'fa-triangle-exclamation' },
};

export function resultStyle(result) {
    return RESULT_STYLES[result] ?? RESULT_STYLES.error;
}

// Durée lisible : 5 min, 1 h 05.
export function formatWorked(seconds) {
    const minutes = Math.max(0, Math.round((Number(seconds) || 0) / 60));
    if (minutes < 60) return `${minutes} min`;
    return `${Math.floor(minutes / 60)} h ${String(minutes % 60).padStart(2, '0')}`;
}

// Quantité saisie dans la fenêtre de fin : entier >= 0, sinon null (refusée).
export function parseQty(value) {
    const text = String(value ?? '').trim();
    if (text === '') return 0;
    return /^\d+$/.test(text) ? parseInt(text, 10) : null;
}

// Garde les derniers scans, le plus récent en tête.
export function pushHistory(history, entry, size = 5) {
    return [entry, ...history].slice(0, size);
}
