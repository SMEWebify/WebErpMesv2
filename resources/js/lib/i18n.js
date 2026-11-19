/** Langue de la page (<html lang>), « fr » par défaut. */
export function pageLocale() {
    return (typeof document !== 'undefined' && document.documentElement.lang) || 'fr';
}

// Textes communs aux briques partagées (tableaux, pagination…), posés une fois par le
// gabarit (resources/views/vendor/adminlte/master.blade.php, <meta name="ui-common">,
// resources/lang/*/ui_common.php). Absents (test, page hors gabarit) : repli français.
let common = null;
export function commonText(key, fallback) {
    if (common === null) {
        try {
            common = JSON.parse((typeof document !== 'undefined' && document.querySelector('meta[name="ui-common"]')?.content) || '{}');
        } catch {
            common = {};
        }
    }
    return common?.[key] ?? fallback;
}

const FR_MONTHS_SHORT = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

/**
 * Mois abrégés de janvier à décembre : les libellés historiques en français,
 * sinon ceux du navigateur pour la langue de la page.
 */
export function shortMonths(locale = pageLocale()) {
    if (String(locale).toLowerCase().startsWith('fr')) return FR_MONTHS_SHORT;
    try {
        const format = new Intl.DateTimeFormat(locale, { month: 'short' });
        return Array.from({ length: 12 }, (_, m) => format.format(new Date(2000, m, 1)));
    } catch {
        return FR_MONTHS_SHORT;
    }
}

/**
 * Remplace les :paramètres d'un texte traduit (gabarit Laravel) en une seule passe.
 * La clé la plus longue qui correspond gagne (:count avant :c) ; un :mot qui n'est
 * pas un paramètre est laissé tel quel.
 */
export function fmt(template, params = {}) {
    if (template === null || template === undefined) return '';
    const keys = Object.keys(params).sort((a, b) => b.length - a.length);
    return String(template).replace(/:([a-z_][a-z0-9_]*)/gi, (match, word) => {
        const key = keys.find((k) => word.startsWith(k));
        if (key === undefined) return match;
        const value = params[key];
        return (value === null || value === undefined ? '' : String(value)) + word.slice(key.length);
    });
}
