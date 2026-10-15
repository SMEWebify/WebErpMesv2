import React from 'react';
import { formatCurrency } from '../../utils';

// Couleur d'un encours selon la part du plafond consommée :
// < 80 % vert, jusqu'au plafond orange, au-delà rouge.
export function creditTheme(ratio) {
    if (ratio === null || ratio === undefined) return 'secondary';
    if (ratio > 1) return 'danger';
    if (ratio >= 0.8) return 'warning';
    return 'success';
}

// Badge « encours / plafond » (liste des sociétés). `credit` vient de
// CreditExposure::toArray() ; null quand la société n'a pas de plafond.
export default function CreditBadge({ credit, currency, locale }) {
    if (!credit || credit.ratio === null) return <span className="text-body-secondary">—</span>;

    const title = `${formatCurrency(credit.total, currency, locale)} / ${formatCurrency(credit.limit, currency, locale)}`;

    return (
        <span className={`badge text-bg-${creditTheme(credit.ratio)}`} title={title}>
            {credit.level === 'block' && <i className="fas fa-lock me-1" />}
            {Math.round(credit.ratio * 100)} %
        </span>
    );
}
