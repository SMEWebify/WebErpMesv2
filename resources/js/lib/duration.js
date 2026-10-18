/**
 * Temps opératoires : conversion heures ⇄ minutes aux bords (saisie, affichage).
 *
 * Le stockage reste en HEURES (tasks.seting_time / unit_time, decimal(10,3)),
 * comme l'API, les exports et les capacités. Seule l'unité affichée change, selon
 * le réglage société (Administration → Unité des temps), lu dans la balise
 * <meta name="time-unit"> posée par le layout. Équivalent PHP : App\Support\TimeDisplay.
 *
 * Règle d'arrondi à l'affichage en minutes : `time-unit-decimals` décimales
 * (config/display.php, 1 par défaut), zéros de fin retirés (30 min, 1.5 min).
 * Règle de saisie : minutes / 60, arrondi à 3 décimales d'heure (= le stockage).
 */

export const HOURS = 'hours';
export const MINUTES = 'minutes';

const STORED_DECIMALS = 3;

function readMeta(name, doc) {
    try {
        return (doc ?? document).querySelector(`meta[name="${name}"]`)?.getAttribute('content') ?? null;
    } catch {
        return null;
    }
}

/** Unité d'affichage courante : 'hours' (défaut) ou 'minutes'. */
export function getTimeUnit(doc) {
    return readMeta('time-unit', doc) === MINUTES ? MINUTES : HOURS;
}

/** Décimales affichées en minutes (config/display.php → meta). */
export function getMinutesDecimals(doc) {
    const n = parseInt(readMeta('time-unit-decimals', doc), 10);
    return Number.isFinite(n) && n >= 0 ? n : 1;
}

export function unitLabel(unit = getTimeUnit()) {
    return unit === MINUTES ? 'min' : 'h';
}

function roundTo(value, decimals) {
    const f = 10 ** decimals;
    return Math.round((value + Number.EPSILON) * f) / f;
}

/** Texte numérique → nombre ; accepte la virgule française. Vide / illisible → null. */
export function parseNumber(input) {
    if (typeof input === 'number') return Number.isFinite(input) ? input : null;
    if (input === null || input === undefined) return null;
    const s = String(input).trim().replace(/\s/g, '').replace(',', '.');
    if (s === '') return null;
    const n = Number(s);
    return Number.isFinite(n) ? n : null;
}

/**
 * Saisie dans l'unité d'affichage → heures à stocker (3 décimales).
 * parseDuration('30', 'minutes') = 0.5 ; parseDuration('0,5', 'hours') = 0.5.
 * Vide ou illisible → null (l'appelant décide : champ vide ou 0).
 */
export function parseDuration(input, unit = getTimeUnit()) {
    const n = parseNumber(input);
    if (n === null) return null;
    return roundTo(unit === MINUTES ? n / 60 : n, STORED_DECIMALS);
}

/** Heures stockées → nombre dans l'unité d'affichage (minutes arrondies). */
export function hoursToUnit(hours, unit = getTimeUnit(), decimals = getMinutesDecimals()) {
    const n = parseNumber(hours) ?? 0;
    return unit === MINUTES ? roundTo(n * 60, decimals) : n;
}

/** Valeur d'un champ de saisie : heures stockées → texte dans l'unité ('' si vide). */
export function toInputValue(hours, unit = getTimeUnit(), decimals = getMinutesDecimals()) {
    const n = parseNumber(hours);
    if (n === null) return '';
    return String(hoursToUnit(n, unit, decimals));
}

/**
 * Heures stockées → texte avec unité.
 * En minutes : « 30 min », « 1.5 min ».
 * En heures : comportement historique, valeur telle quelle ou `toFixed(hoursDecimals)`,
 * suffixe `hoursSuffix` (' h' par défaut ; 'h' collé dans le tableau de gamme).
 */
export function formatDuration(hours, {
    unit = getTimeUnit(),
    decimals = getMinutesDecimals(),
    hoursDecimals,
    hoursSuffix = ' h',
} = {}) {
    if (unit === MINUTES) {
        return `${hoursToUnit(hours, MINUTES, decimals)} min`;
    }
    const n = parseNumber(hours) ?? 0;
    const text = hoursDecimals === undefined ? String(hours ?? 0) : n.toFixed(hoursDecimals);
    return `${text}${hoursSuffix}`;
}
