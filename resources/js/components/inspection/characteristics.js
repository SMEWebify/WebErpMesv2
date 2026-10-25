// Cotes d'un plan de contrôle : symboles de tolérancement géométrique (ISO 1101 /
// ASME Y14.5) et libellé court affiché dans les listes et le bulleur.

export const GDT_SYMBOLS = {
    straightness:     '⏤',
    flatness:         '⏥',
    circularity:      '○',
    cylindricity:     '⌭',
    profile_line:     '⌒',
    profile_surface:  '⌓',
    perpendicularity: '⟂',
    parallelism:      '∥',
    angularity:       '∠',
    position:         '⌖',
    concentricity:    '◎',
    symmetry:         '⌯',
    circular_runout:  '↗',
    total_runout:     '⌰',
};

export const TYPES = ['dimension', 'diameter', 'radius', 'angle', 'gdt', 'thread', 'roughness', 'note'];

export const TYPE_PREFIX = { diameter: '⌀', radius: 'R', thread: 'M', roughness: 'Ra ' };

export const MODIFIERS = { M: 'Ⓜ', L: 'Ⓛ' };

export const FREQUENCIES = ['all', 'first', 'every_n', 'sample'];

const num = v => (v === null || v === undefined || v === '' ? null : Number(v));

export function formatNumber(value) {
    const n = num(value);
    if (n === null || Number.isNaN(n)) return '';
    return String(Number(n.toFixed(4)));
}

/** Tolérance d'une cote : « ±0.1 », « +0.2 / -0.1 », ou vide. */
export function formatTolerance(point) {
    const lo = num(point.tol_min);
    const hi = num(point.tol_max);
    if (lo === null && hi === null) return '';
    if (lo !== null && hi !== null && Math.abs(lo + hi) < 1e-9 && hi !== 0) return `±${formatNumber(hi)}`;
    const sign = v => (v >= 0 ? `+${formatNumber(v)}` : formatNumber(v));
    return [hi !== null ? sign(hi) : null, lo !== null ? sign(lo) : null].filter(Boolean).join(' / ');
}

/** Limites acceptées « min – max », quand nominal et tolérances sont connus. */
export function formatLimits(point) {
    const n = num(point.nominal_value);
    const lo = num(point.tol_min);
    const hi = num(point.tol_max);
    if (n === null || lo === null || hi === null || point.characteristic_type === 'gdt') return '';
    return `${formatNumber(n + lo)} – ${formatNumber(n + hi)}`;
}

/** Cadre de tolérance découpé en cases : [symbole, zone, références…]. */
export function frameCells(point) {
    const zone = `${point.diameter_zone ? '⌀' : ''}${formatNumber(point.tol_max)}${point.material_modifier ? ` ${MODIFIERS[point.material_modifier]}` : ''}`;
    const datums = (point.datums || '').split(',').map(d => d.trim()).filter(Boolean);
    return [GDT_SYMBOLS[point.gdt_symbol] ?? '?', zone, ...datums];
}

const NUM = '[-+]?(?:\\d+(?:[.,]\\d*)?|[.,]\\d+)';
const toNum = s => Number(String(s).replace(',', '.'));

/**
 * Décode le texte d'une cote lu dans le PDF : « 2X ⌀12 ±0,1 », « R0.25 », « 45° »,
 * « 12 +0.2/-0.1 », « M8x1.25 », « Ra 1.6 ». Renvoie les champs de la cote, ou
 * null quand le texte ne ressemble pas à une cote (cadre de tolérance, note…) :
 * c'est alors à l'IA de le lire.
 */
export function parseDimensionText(raw) {
    if (!raw) return null;
    let text = raw.replace(/\s+/g, ' ').trim();
    const out = { label: raw.trim().slice(0, 255) };

    const count = text.match(/^\(?(\d+)\s*[xX×]\)?\s*/);
    if (count) { out.count = Number(count[1]); text = text.slice(count[0].length); }

    // Cote double « 19.05mm ±0.25 .75in ±.01 » ou « [3.61in] » : on garde les millimètres.
    text = text.replace(/\[[^\]]*\]/g, ' ').replace(/(^|\s)[\d.,]+\s*in\b.*$/i, '').trim();

    let type = 'dimension';
    if (/^(?:Ra|Rz)\s*/i.test(text)) { type = 'roughness'; text = text.replace(/^(?:Ra|Rz)\s*/i, ''); }
    else if (/^[⌀Ø∅]/.test(text)) { type = 'diameter'; text = text.slice(1).trim(); }
    else if (/^R\s*\d/i.test(text)) { type = 'radius'; text = text.replace(/^R\s*/i, ''); }
    else if (/^M\s*\d/.test(text)) { type = 'thread'; text = text.replace(/^M\s*/, ''); }

    const nominal = text.match(new RegExp(`^(${NUM})\\s*(mm|°)?`));
    if (!nominal) return null;
    out.nominal_value = toNum(nominal[1]);
    if (nominal[2] === '°' || /^\d+(?:[.,]\d+)?\s*°/.test(text)) type = 'angle';
    if (nominal[2] === 'mm') out.unit = 'mm';
    let rest = text.slice(nominal[0].length).trim();
    if (type === 'thread') rest = rest.replace(/^[xX×]\s*[\d.,]+/, '').trim();

    const sym = rest.match(new RegExp(`^(?:±|\\+/-|\\+-)\\s*(${NUM})`));
    // Écarts « +0.2/-0.1 », « 0/-0.1 », « +0.2 -0.1 » (au moins un signe ou un zéro).
    const pair = rest.match(new RegExp(`^(${NUM})\\s*(?:/|\\s)\\s*(${NUM})`));
    // Seconde limite « 12,1 / 11,9 » : le nominal lu est la première.
    const limit = rest.match(new RegExp(`^/\\s*(${NUM})`));
    if (sym) {
        const t = Math.abs(toNum(sym[1]));
        out.tol_min = -t; out.tol_max = t;
    } else if (pair && (/^[+-]/.test(pair[1]) || /^[+-]/.test(pair[2]) || toNum(pair[1]) === 0 || toNum(pair[2]) === 0)) {
        const a = toNum(pair[1]), b = toNum(pair[2]);
        out.tol_min = Math.min(a, b); out.tol_max = Math.max(a, b);
    } else if (limit) {
        const hi = Math.max(out.nominal_value, toNum(limit[1]));
        const lo = Math.min(out.nominal_value, toNum(limit[1]));
        out.nominal_value = Number(((hi + lo) / 2).toFixed(6));
        out.tol_min = Number((lo - out.nominal_value).toFixed(6));
        out.tol_max = Number((hi - out.nominal_value).toFixed(6));
    }

    out.characteristic_type = type;
    if (type === 'angle') out.unit = '°';
    return out;
}

/** Libellé d'une ligne : « ⌀12 ±0.1 », « R0.25 », « ⟂ 0.03 A,B »… */
export function describe(point) {
    if (point.characteristic_type === 'note') return point.label || '';
    if (point.characteristic_type === 'gdt') {
        return frameCells(point).join(' ');
    }
    const prefix = TYPE_PREFIX[point.characteristic_type] ?? '';
    const suffix = point.characteristic_type === 'angle' ? '°' : '';
    const nominal = formatNumber(point.nominal_value);
    return `${prefix}${nominal}${suffix} ${formatTolerance(point)}`.trim();
}
