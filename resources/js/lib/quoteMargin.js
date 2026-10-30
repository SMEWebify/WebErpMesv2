// Marge des lignes de devis (App\Services\Quotes\QuoteMargin) : information
// interne, jamais imprimée. `line.margin` est calculé par le serveur ; une ligne
// de présentation n'en porte pas.

export const MARGIN_COLUMN_STORAGE_KEY = 'quote_lines_margin_column';

/**
 * État de la marge d'une ligne pour l'affichage.
 *  - 'none'  : pas de coût de revient connu (aucun taux calculable) ;
 *  - 'below' : taux sous le seuil, la ligne est mise en évidence ;
 *  - 'ok'    : sinon.
 *
 * @param {{margin_rate?: number|null, has_cost?: boolean}|null|undefined} margin
 * @param {number} threshold  seuil en % du coût (config quotes.margin_alert_threshold)
 * @returns {'none'|'below'|'ok'}
 */
export function marginLevel(margin, threshold = 0) {
    if (!margin || !margin.has_cost || margin.margin_rate === null || margin.margin_rate === undefined) {
        return 'none';
    }
    return Number(margin.margin_rate) < Number(threshold ?? 0) ? 'below' : 'ok';
}

/**
 * Taux de marge affiché (« 12,5 % »), tiret sans coût.
 *
 * @param {number|null|undefined} rate
 * @param {string} locale
 */
export function formatMarginRate(rate, locale = 'fr-FR') {
    if (rate === null || rate === undefined || Number.isNaN(Number(rate))) return '—';
    return `${Number(rate).toLocaleString(locale, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} %`;
}

/**
 * Colonne marge affichée ? Préférence du navigateur, visible par défaut.
 * Le stockage peut être indisponible (navigation privée) : on retombe sur le défaut.
 */
export function readMarginColumnVisible(storage = globalThis.localStorage) {
    try {
        return storage?.getItem(MARGIN_COLUMN_STORAGE_KEY) !== '0';
    } catch {
        return true;
    }
}

export function writeMarginColumnVisible(visible, storage = globalThis.localStorage) {
    try {
        storage?.setItem(MARGIN_COLUMN_STORAGE_KEY, visible ? '1' : '0');
    } catch {
        // préférence non mémorisée, sans conséquence
    }
}
