/**
 * Format a quantity value: remove trailing decimal zeros.
 * 3.000 → "3"   |   1.500 → "1.5"   |   1.125 → "1.125"
 */
export function formatQty(value) {
    const n = parseFloat(value ?? 0);
    if (isNaN(n)) return String(value ?? '');
    return String(parseFloat(n.toFixed(3)));
}

/**
 * Format an ISO date ("2026-10-09") for display, without timezone shift.
 * Empty → "—". Unparseable → returned as is.
 */
export function formatDate(dateStr, locale) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat(locale || 'fr-FR').format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

/**
 * Format an amount with 2 decimals: 1234.5 → "1 234,50 €".
 */
export function formatCurrency(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style:    'currency',
            currency: currency || 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${Number(amount).toFixed(2)} ${currency ?? '€'}`;
    }
}

/**
 * Format an amount rounded to the unit: 1234.5 → "1 235 €" (KPI, totals).
 */
export function formatCurrencyRounded(amount, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style:    'currency',
            currency: currency || 'EUR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    } catch {
        return `${Number(amount).toFixed(0)} ${currency ?? '€'}`;
    }
}
