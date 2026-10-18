import React, { useState } from 'react';
import { formatQty } from '../../utils';

// Cellules « Liens » et « Qté » de la gamme opératoire (TaskManagePage).
// Par défaut une opération suit la précédente (fin-début) avec la quantité de
// la ligne : on ne saisit que les exceptions, directement dans le tableau.

const LINKS_HELP = 'Exemples : 20FD;30FD · 20DD+2h · 20DD+10pc;20FF+10pc — vide = après l\'opération précédente';

/**
 * Liens d'une opération en notation courte. Un clic ouvre la saisie ;
 * Entrée enregistre, Échap annule.
 *
 * @param {{implicit: boolean, notation: string, display: string}|undefined} info
 * @param {(notation: string) => Promise<{ok: boolean, error?: string}>} onSave
 */
export function LinksCell({ info, canEdit, onSave }) {
    const [editing, setEditing] = useState(false);
    const [value, setValue]     = useState('');
    const [error, setError]     = useState(null);
    const [saving, setSaving]   = useState(false);

    const start = () => {
        if (!canEdit || editing) return;
        setValue(info && !info.implicit ? info.notation : '');
        setError(null);
        setEditing(true);
    };

    const commit = async () => {
        setSaving(true);
        const result = await onSave(value.trim());
        setSaving(false);
        if (result.ok) {
            setEditing(false);
            setError(null);
        } else {
            setError(result.error ?? 'Erreur');
        }
    };

    if (editing) {
        return (
            <td style={{ minWidth: 150 }}>
                <input
                    autoFocus
                    aria-label="Liens"
                    className={`form-control form-control-sm${error ? ' is-invalid' : ''}`}
                    value={value}
                    placeholder="20FD;30FD"
                    title={LINKS_HELP}
                    readOnly={saving}
                    onChange={(e) => setValue(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') { e.preventDefault(); commit(); }
                        if (e.key === 'Escape') { setEditing(false); setError(null); }
                    }}
                />
                {error && <div className="invalid-feedback d-block" style={{ fontSize: 11 }}>{error}</div>}
            </td>
        );
    }

    return (
        <td
            onClick={start}
            title={canEdit ? LINKS_HELP : undefined}
            style={{ fontSize: 12, cursor: canEdit ? 'pointer' : 'default', whiteSpace: 'nowrap' }}
        >
            {(!info || !info.display) && <span className="text-muted">—</span>}
            {info && info.display && info.implicit && <span className="text-muted fst-italic">{info.display}</span>}
            {info && info.display && !info.implicit && <code style={{ fontSize: 11 }}>{info.display}</code>}
            {canEdit && <i className="fas fa-pen ms-1" style={{ fontSize: 9, opacity: 0.4 }} />}
        </td>
    );
}

/**
 * Quantité traitée par l'opération : vide = quantité de la ligne (affichée en
 * gris). Sert au partage d'un lot entre deux machines ; aucune vérification
 * de la somme.
 *
 * @param {(value: number|null) => Promise<{ok: boolean, error?: string}>} onSave
 */
export function PlannedQtyCell({ plannedQty, lineQty, canEdit, onSave }) {
    const [editing, setEditing] = useState(false);
    const [value, setValue]     = useState('');
    const [error, setError]     = useState(null);
    const [saving, setSaving]   = useState(false);

    const hasOwn = plannedQty !== null && plannedQty !== undefined;

    const start = () => {
        if (!canEdit || editing) return;
        setValue(hasOwn ? String(plannedQty) : '');
        setError(null);
        setEditing(true);
    };

    const commit = async () => {
        const raw = value.trim().replace(',', '.');
        const parsed = raw === '' ? null : Number(raw);
        if (parsed !== null && (Number.isNaN(parsed) || parsed < 0)) {
            setError('Quantité invalide');
            return;
        }
        setSaving(true);
        const result = await onSave(parsed);
        setSaving(false);
        if (result.ok) {
            setEditing(false);
            setError(null);
        } else {
            setError(result.error ?? 'Erreur');
        }
    };

    if (editing) {
        return (
            <td style={{ minWidth: 80 }}>
                <input
                    autoFocus
                    aria-label="Quantité de l'opération"
                    inputMode="decimal"
                    className={`form-control form-control-sm${error ? ' is-invalid' : ''}`}
                    value={value}
                    placeholder={formatQty(lineQty)}
                    title="Vide = quantité de la ligne"
                    readOnly={saving}
                    onChange={(e) => setValue(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') { e.preventDefault(); commit(); }
                        if (e.key === 'Escape') { setEditing(false); setError(null); }
                    }}
                />
                {error && <div className="invalid-feedback d-block" style={{ fontSize: 11 }}>{error}</div>}
            </td>
        );
    }

    return (
        <td
            onClick={start}
            title={canEdit ? 'Quantité traitée par cette opération — vide = quantité de la ligne' : undefined}
            style={{ textAlign: 'right', fontSize: 12, cursor: canEdit ? 'pointer' : 'default' }}
        >
            {hasOwn
                ? <strong>{formatQty(plannedQty)}</strong>
                : <span className="text-muted">{formatQty(lineQty)}</span>}
        </td>
    );
}
