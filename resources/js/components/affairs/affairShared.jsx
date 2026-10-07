/**
 * Outils partagés par la synthèse et l'historique d'une affaire
 * (AffairSummary, AffairTimeline) : requêtes, gabarits de traduction,
 * formats de date et de montant, couleurs des statuts.
 */

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function request(url, { method = 'GET', body } = {}) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    let data = null;
    try { data = await response.json(); } catch { /* corps vide */ }

    if (!response.ok) {
        const error = new Error(data?.message || `HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return data;
}

/**
 * Remplace les :paramètres d'un gabarit Laravel en une seule passe (une valeur
 * substituée n'est jamais relue). La clé la plus longue qui correspond gagne
 * (:received avant :re…) ; un paramètre absent devient vide.
 */
export function fmt(template, params = {}) {
    if (typeof template !== 'string') return '';
    const keys = Object.keys(params).sort((a, b) => b.length - a.length);
    return template.replace(/:([a-z_]+)/gi, (match, word) => {
        const key = keys.find((k) => word.startsWith(k));
        if (key === undefined) return '';
        const value = params[key];
        return (value === null || value === undefined ? '' : String(value)) + word.slice(key.length);
    });
}

/** Transforme 'Y-m-d' ou 'Y-m-d H:i' en Date locale (sans décalage de fuseau). */
export function parseDate(value) {
    if (!value) return null;
    const match = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/.exec(String(value));
    if (!match) return null;
    const [, y, m, d, hh, mm] = match;
    return new Date(Number(y), Number(m) - 1, Number(d), Number(hh ?? 0), Number(mm ?? 0));
}

export function formatDate(value, locale = 'fr') {
    const date = parseDate(value);
    if (!date) return value ? String(value) : '—';
    try { return new Intl.DateTimeFormat(locale).format(date); } catch { return String(value); }
}

export function formatDateTime(value, locale = 'fr') {
    const date = parseDate(value);
    if (!date) return value ? String(value) : '—';
    // Une date sans heure ne doit pas afficher « 00:00 »
    if (!/\d{2}:\d{2}/.test(String(value))) return formatDate(value, locale);
    try {
        return new Intl.DateTimeFormat(locale, { dateStyle: 'short', timeStyle: 'short' }).format(date);
    } catch { return String(value); }
}

export function formatAmount(amount, currency = 'EUR', locale = 'fr') {
    if (amount === null || amount === undefined || amount === '') return '—';
    const number = Number(amount);
    try {
        return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(number);
    } catch { return `${number.toFixed(2)} ${currency}`; }
}

export function formatNumber(value, locale = 'fr', digits = 1) {
    if (value === null || value === undefined) return '—';
    try {
        return new Intl.NumberFormat(locale, { maximumFractionDigits: digits }).format(Number(value));
    } catch { return String(value); }
}

/** Aujourd'hui au format 'Y-m-d', heure locale. */
export function todayIso() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/** Couleur de badge (classes Bootstrap 5.3 text-bg-*) par famille de statut. */
const STATUS_COLORS = {
    quote_status:    { 1: 'secondary', 2: 'info', 3: 'success', 4: 'danger', 5: 'dark', 6: 'light' },
    order_status:    { 0: 'warning', 1: 'secondary', 2: 'primary', 3: 'success', 4: 'info', 5: 'warning', 6: 'danger' },
    purchase_status: { 1: 'secondary', 2: 'primary', 3: 'info', 4: 'success', 5: 'danger' },
    invoice_status:  { 1: 'secondary', 2: 'info', 3: 'warning', 4: 'danger', 5: 'success' },
    visit_status:    { 1: 'secondary', 2: 'success' },
    event_type:      { 1: 'info', 2: 'secondary', 3: 'primary', 4: 'success' },
};

/**
 * Libellé et couleur d'un statut. Le tableau de traduction peut arriver en
 * objet ({"1": …}) ou en liste JSON (clés 0..n), l'accès par index marche
 * dans les deux cas.
 */
export function statusBadge(trans, family, statu) {
    if (statu === null || statu === undefined) return null;
    const label = trans?.[family]?.[statu] ?? String(statu);
    const color = STATUS_COLORS[family]?.[statu] ?? 'secondary';
    return { label, className: `badge text-bg-${color}` };
}

export function StatusBadge({ trans, family, statu }) {
    const badge = statusBadge(trans, family, statu);
    if (!badge) return null;
    return <span className={badge.className}>{badge.label}</span>;
}
