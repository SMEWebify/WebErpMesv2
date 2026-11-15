// Import DPGF : passage de l'aperçu calculé par le serveur aux lignes envoyées
// à la création. Le serveur revalide tout ; ces fonctions ne font que préparer.

export const DPGF_FIELDS = ['code', 'label', 'unit', 'qty', 'price', 'amount'];
export const DPGF_TYPES = ['article', 'section', 'subtotal', 'text', 'ignore'];

// Lignes de l'aperçu → lignes éditables.
export function toDrafts(lines = []) {
    return lines.map((line) => ({
        key: line.row,
        row: line.row,
        type: line.type,
        code: line.code ?? '',
        label: line.label ?? '',
        description: line.description ?? null,
        qty: line.qty ?? '',
        methods_units_id: line.methods_units_id ?? '',
        unit_text: line.unit_text ?? null,
        selling_price: line.selling_price ?? '',
        qty_missing: !!line.qty_missing,
    }));
}

function number(value) {
    const n = parseFloat(String(value ?? '').replace(',', '.'));
    return Number.isFinite(n) && n >= 0 ? n : null;
}

// Lignes gardées (non ignorées, avec un libellé sauf sous-total), prêtes pour l'envoi.
export function toPayload(drafts = []) {
    return drafts
        .filter((d) => d.type !== 'ignore' && (d.type === 'subtotal' || String(d.label ?? '').trim() !== ''))
        .map((d) => {
            const line = { type: d.type, label: String(d.label ?? '').trim().slice(0, 255), description: d.description ?? null };
            if (d.type !== 'article') return line;
            return {
                ...line,
                code: String(d.code ?? '').trim().slice(0, 255) || null,
                qty: number(d.qty) ?? 0,
                methods_units_id: d.methods_units_id ? Number(d.methods_units_id) : null,
                selling_price: number(d.selling_price) ?? 0,
            };
        });
}

// Compte par type, et points à signaler avant la création.
export function summarize(drafts = []) {
    const counts = Object.fromEntries(DPGF_TYPES.map((t) => [t, 0]));
    let qtyMissing = 0;
    let unitUnknown = 0;
    for (const d of drafts) {
        counts[d.type] = (counts[d.type] ?? 0) + 1;
        if (d.type !== 'article') continue;
        if (number(d.qty) === null || number(d.qty) === 0) qtyMissing += 1;
        if (d.unit_text && !d.methods_units_id) unitUnknown += 1;
    }
    return { counts, qtyMissing, unitUnknown, kept: toPayload(drafts).length };
}
