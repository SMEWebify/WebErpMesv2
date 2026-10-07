import React from 'react';

/**
 * Badge de statut piloté par la table `config` de l'écran : { [statu]: { badge, label } },
 * `label` étant une clé de `trans`.
 *
 * `fallback` fixe le texte quand le statut ou sa traduction manque, comme le faisait chaque écran :
 * - 'key'   : la clé de traduction (ou le statut brut s'il est inconnu)
 * - 'value' : le statut brut (sauf si `trans.unknown` existe pour un statut inconnu)
 */
export default function StatusBadge({ statu, config, trans, fallback = 'key' }) {
    if (fallback === 'value') {
        const cfg = config[statu] ?? { badge: 'badge-secondary', label: 'unknown' };
        return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? statu}</span>;
    }
    const cfg = config[statu] ?? { badge: 'badge-secondary', label: String(statu) };
    return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
}
