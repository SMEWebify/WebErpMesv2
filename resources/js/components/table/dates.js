/**
 * Date comparable « AAAA-MM-JJ » depuis les formats renvoyés par les endpoints :
 * ISO (« 2026-05-01 », « 2026-05-01T08:00:00Z ») ou français (« 01/05/2026 »).
 * Renvoie '' si la valeur est vide ou illisible.
 */
export function toISODate(value) {
    if (!value) return '';
    const str = String(value);
    if (str.includes('/')) {
        const [d, m, y] = str.split('/');
        if (!y) return '';
        return `${y.slice(0, 4)}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }
    return /^\d{4}-\d{2}-\d{2}/.test(str) ? str.slice(0, 10) : '';
}
