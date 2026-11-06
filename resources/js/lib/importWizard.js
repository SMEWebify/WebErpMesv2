// Assistant d'import : correspondance champ → colonne mémorisée par navigateur.
//
// On mémorise, par type d'import, le nom d'en-tête choisi pour chaque champ
// (« Raison sociale » → label). Au fichier suivant, une colonne au même nom
// reprend ce choix ; sinon la proposition du serveur reste.

const storageKey = (type) => `import_mapping_${type}`;

export const normalize = (value) => String(value ?? '')
    .toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9%]+/g, ' ')
    .trim();

function read(type) {
    try {
        return JSON.parse(window.localStorage.getItem(storageKey(type)) ?? '{}') || {};
    } catch {
        return {};
    }
}

/**
 * Proposition du serveur, corrigée par les choix mémorisés pour des en-têtes
 * identiques (la colonne reprise est retirée du champ qui l'avait).
 */
export function restoreMapping(type, columns, suggested) {
    const saved = read(type);
    const mapping = { ...suggested };
    const byHeader = new Map(columns.filter(c => c.header).map(c => [normalize(c.header), c.index]));

    for (const [field, header] of Object.entries(saved)) {
        const index = byHeader.get(header);
        if (index !== undefined) {
            for (const [other, col] of Object.entries(mapping)) {
                if (col === index && other !== field) delete mapping[other];
            }
            mapping[field] = index;
        }
    }

    return mapping;
}

export function saveMapping(type, columns, mapping) {
    const headers = new Map(columns.map(c => [c.index, c.header ? normalize(c.header) : null]));
    const saved = {};
    for (const [field, col] of Object.entries(mapping)) {
        if (col !== null && col !== undefined && headers.get(col)) saved[field] = headers.get(col);
    }
    try {
        window.localStorage.setItem(storageKey(type), JSON.stringify(saved));
    } catch {
        // stockage indisponible (navigation privée) : rien à mémoriser
    }
}

export function requiredUnmapped(fields, mapping) {
    return fields.filter(f => f.required && (mapping[f.key] === undefined || mapping[f.key] === null));
}

/** « B — Raison sociale » si la 1re ligne est un en-tête, sinon « Colonne B ». */
export function columnLabel(column, header, t) {
    const name = (t.column_n ?? 'Colonne :letter').replace(':letter', column.letter);
    return header && column.header ? `${column.letter} — ${column.header}` : name;
}
