/**
 * Format a quantity value: remove trailing decimal zeros.
 * 3.000 → "3"   |   1.500 → "1.5"   |   1.125 → "1.125"
 */
export function formatQty(value) {
    const n = parseFloat(value ?? 0);
    if (isNaN(n)) return String(value ?? '');
    return String(parseFloat(n.toFixed(3)));
}
