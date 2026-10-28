import React from 'react';

/**
 * Repères d'équipement (tags) d'une ligne de devis ou de commande.
 *
 * Le serveur stocke une liste (App\Casts\EquipmentTags) ; l'écran les saisit
 * dans un seul champ, séparés par une virgule, un point-virgule ou un retour
 * à la ligne. Mêmes règles des deux côtés : repères rognés, vides et doublons
 * retirés.
 */

/** Texte saisi → liste envoyée au serveur. */
export function parseTags(text) {
    if (Array.isArray(text)) return parseTags(text.join('\n'));
    const tags = [];
    String(text ?? '')
        .split(/[,;\r\n]+/)
        .map((t) => t.trim())
        .filter(Boolean)
        .forEach((t) => { if (!tags.includes(t)) tags.push(t); });
    return tags;
}

/** Liste reçue du serveur → texte du champ. */
export function formatTags(tags) {
    return (Array.isArray(tags) ? tags : []).join(', ');
}

/** Plus de repères que d'équipements : simple avertissement, jamais bloquant. */
export function tagsExceedQty(tags, qty) {
    const q = Number(qty);
    return Number.isFinite(q) && q > 0 && tags.length > q;
}

const DEFAULT_TRANS = {
    label:       'Repères équipement',
    placeholder: 'Un repère par équipement, séparés par une virgule',
    too_many:    ':count repères pour une quantité de :qty',
};

const fill = (text, values) => Object.entries(values).reduce((s, [k, v]) => s.replace(`:${k}`, v), text);

/** Champ du tiroir de ligne : texte libre, compteur et avertissement. */
export function EquipmentTagsInput({ id = 'line-equipment-tag', value, qty, onChange, disabled, error, trans = {} }) {
    const t    = { ...DEFAULT_TRANS, ...trans };
    const tags = parseTags(value);

    return (
        <div className="mb-3">
            <label className="mb-1 small fw-bold" htmlFor={id}>
                {t.label}
                {tags.length > 0 && <span className="badge text-bg-secondary ms-1">{tags.length}</span>}
            </label>
            <textarea id={id} rows={2} className={`form-control form-control-sm ${error ? 'is-invalid' : ''}`}
                placeholder={t.placeholder} value={value ?? ''} disabled={disabled}
                onChange={(e) => onChange(e.target.value)} />
            {error && <div className="invalid-feedback d-block">{error}</div>}
            {tagsExceedQty(tags, qty) && (
                <div className="form-text text-warning">
                    <i className="fas fa-exclamation-triangle me-1" />
                    {fill(t.too_many, { count: tags.length, qty: Number(qty) })}
                </div>
            )}
        </div>
    );
}

/** Repères affichés sous le libellé d'une ligne. */
export function EquipmentTagBadges({ tags, title }) {
    if (!Array.isArray(tags) || tags.length === 0) return null;

    return (
        <div className="small text-truncate" title={tags.join(', ')}>
            <i className="fas fa-tag text-body-secondary me-1" title={title} />
            {tags.map((tag) => (
                <span key={tag} className="badge bg-body-tertiary text-body border me-1">{tag}</span>
            ))}
        </div>
    );
}
