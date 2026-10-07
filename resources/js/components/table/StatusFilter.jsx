import React from 'react';

/**
 * Boutons bascule de filtre par statut, colorés comme le badge du statut.
 *
 * Deux façons de brancher l'écran :
 * - `onToggle(id)` : l'écran gère lui-même la liste (et le retour en page 1)
 * - `onChange(next)` : le composant calcule la liste ; `allowEmpty={false}` garde
 *   au moins un statut coché (Devis, Factures, Réceptions, N° de série)
 */
export default function StatusFilter({
    config,
    ids,
    selected,
    onToggle,
    onChange,
    trans,
    allowEmpty = true,
    fallback = 'key',
    buttonType,
}) {
    const list = ids ?? Object.keys(config).map(Number);

    const toggle = (id) => {
        if (onToggle) return onToggle(id);
        const next = selected.includes(id) ? selected.filter(s => s !== id) : [...selected, id];
        onChange(next.length || allowEmpty ? next : [id]);
    };

    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {list.map(id => {
                const cfg    = config[id];
                const active = selected.includes(id);
                return (
                    <button
                        key={id}
                        type={buttonType}
                        className={`btn btn-sm ${active ? cfg.badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                        onClick={() => toggle(id)}
                    >
                        {trans[cfg.label] ?? (fallback === 'value' ? id : cfg.label)}
                    </button>
                );
            })}
        </div>
    );
}
