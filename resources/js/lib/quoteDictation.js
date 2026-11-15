// Dictée de devis : passage du brouillon proposé par l'IA aux lignes envoyées
// à la création. Le serveur revalide tout ; ces fonctions ne font que préparer.

export function fill(text, values = {}) {
    return Object.entries(values).reduce(
        (out, [key, value]) => out.replace(`:${key}`, String(value)),
        String(text ?? ''),
    );
}

// Brouillon reçu du serveur → lignes éditables (toutes gardées par défaut).
export function toDrafts(lines = []) {
    return lines.map((line, index) => ({
        key: index,
        keep: true,
        label: line.label ?? '',
        qty: line.qty ?? 1,
        methods_units_id: line.methods_units_id ?? '',
        unit_text: line.unit_text ?? null,
        selling_price: line.selling_price ?? '',
        material: line.material ?? '',
        internal_comment: line.internal_comment ?? '',
    }));
}

function number(value) {
    const n = parseFloat(String(value ?? '').replace(',', '.'));
    return Number.isFinite(n) && n >= 0 ? n : null;
}

function text(value) {
    const t = String(value ?? '').trim();
    return t === '' ? null : t;
}

// Lignes gardées, prêtes pour l'envoi. Prix non dicté = 0 (à saisir ensuite).
export function toPayload(drafts = []) {
    return drafts
        .filter((d) => d.keep && String(d.label ?? '').trim() !== '')
        .map((d) => ({
            label: String(d.label).trim().slice(0, 255),
            qty: number(d.qty) ?? 1,
            methods_units_id: d.methods_units_id ? Number(d.methods_units_id) : null,
            selling_price: number(d.selling_price) ?? 0,
            material: text(d.material),
            internal_comment: text(d.internal_comment),
        }));
}

// Texte transcrit ou dicté ajouté à la suite de ce qui est déjà saisi.
export function appendText(current, added) {
    const a = String(added ?? '').trim();
    if (!a) return current ?? '';
    const c = String(current ?? '').trim();
    return c ? `${c} ${a}` : a;
}
