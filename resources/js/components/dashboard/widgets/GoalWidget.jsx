import React from 'react';
import WidgetCard from '../WidgetCard.jsx';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatCurrency(value, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR', maximumFractionDigits: 0,
        }).format(value ?? 0);
    } catch {
        return (value ?? 0).toFixed(0);
    }
}

function clamp(v, min, max) {
    return Math.min(Math.max(v, min), max);
}

// ─── Radial gauge SVG ─────────────────────────────────────────────────────────

/**
 * Arc SVG semi-circulaire (180°).
 * pct : 0–100
 */
function RadialGauge({ pct, color, size = 200 }) {
    const cx = size / 2;
    const cy = size / 2;
    const R  = size * 0.38;
    const stroke = size * 0.09;

    // Arc de -180° à 0° (gauche → droite par le bas)
    const startAngle = Math.PI;          // gauche
    const endAngle   = 0;                // droite
    const fillAngle  = Math.PI - (clamp(pct, 0, 100) / 100) * Math.PI;

    function arcPath(from, to) {
        const x1 = cx + R * Math.cos(from);
        const y1 = cy + R * Math.sin(from);
        const x2 = cx + R * Math.cos(to);
        const y2 = cy + R * Math.sin(to);
        const large = Math.abs(to - from) > Math.PI ? 1 : 0;
        return `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2}`;
    }

    // Couleur dynamique selon le taux
    const gaugeColor = pct >= 100 ? '#28a745'
        : pct >= 75  ? '#17a2b8'
        : pct >= 50  ? '#ffc107'
        : '#dc3545';

    return (
        <svg
            viewBox={`0 0 ${size} ${size / 2 + stroke}`}
            style={{ width: '100%', maxWidth: size, display: 'block', margin: '0 auto' }}
        >
            {/* Rail gris */}
            <path
                d={arcPath(Math.PI, 0)}
                fill="none"
                stroke="#e9ecef"
                strokeWidth={stroke}
                strokeLinecap="round"
            />
            {/* Arc rempli */}
            {pct > 0 && (
                <path
                    d={arcPath(Math.PI, fillAngle)}
                    fill="none"
                    stroke={gaugeColor}
                    strokeWidth={stroke}
                    strokeLinecap="round"
                    style={{ transition: 'stroke-dashoffset 0.6s ease' }}
                />
            )}
            {/* % au centre */}
            <text
                x={cx}
                y={cy - 2}
                textAnchor="middle"
                fontSize={size * 0.16}
                fontWeight="700"
                fill={gaugeColor}
            >
                {clamp(Math.round(pct), 0, 999)}%
            </text>
        </svg>
    );
}

// ─── GoalWidget ───────────────────────────────────────────────────────────────

/**
 * GoalWidget — progression facturé vs objectif annuel
 *
 * Deux modes :
 *
 *   Mode 1 — données pré-chargées (Blade → props, aucun fetch)
 *     <GoalWidget
 *       invoiced={orderTotaInvoiced}
 *       target={EstimatedBudgets}
 *       trans={trans}
 *       currency="EUR"
 *     />
 *
 *   Mode 2 — calcul depuis données ordersMonthly déjà chargées
 *     <GoalWidget
 *       invoicedRows={data.invoiceMonthlyRecap}
 *       targetRow={data.estimatedBudget[0]}
 *       trans={trans}
 *       currency="EUR"
 *     />
 *
 * Props :
 *   invoiced      number          — total facturé (valeur brute)
 *   target        number          — objectif annuel total (valeur brute)
 *   invoicedRows  array           — [{month, orderSum}] (alternative à invoiced)
 *   targetRow     object          — {amount1..12} (alternative à target)
 *   currency      string          — code devise (défaut 'EUR')
 *   locale        string          — locale Intl (défaut 'fr-FR')
 *   trans         object          — { goal, total_invoiced, annual_target, invoiced_vs_target }
 *   editMode      bool
 *   onRemove      func
 */
export default function GoalWidget({
    invoiced: invoicedProp,
    target: targetProp,
    invoicedRows,
    targetRow,
    currency = 'EUR',
    locale   = 'fr-FR',
    trans    = {},
    editMode = false,
    onRemove,
}) {
    // Calcul facturé
    const invoiced = invoicedProp != null
        ? Number(invoicedProp)
        : (invoicedRows ?? []).reduce((s, r) => s + (parseFloat(r.orderSum) || 0), 0);

    // Calcul objectif
    const target = targetProp != null
        ? Number(targetProp)
        : targetRow
            ? Array.from({ length: 12 }, (_, i) => parseFloat(targetRow[`amount${i + 1}`]) || 0)
                .reduce((s, v) => s + v, 0)
            : 0;

    const pct     = target > 0 ? (invoiced / target) * 100 : 0;
    const isEmpty = invoiced === 0 && target === 0;

    return (
        <WidgetCard
            title={trans.goal ?? 'Objectif annuel'}
            icon="fa-bullseye"
            theme="success"
            empty={isEmpty}
            emptyText={trans.no_target ?? 'Objectif non configuré'}
            editMode={editMode}
            onRemove={onRemove}
        >
            <RadialGauge pct={pct} />

            {/* Détail chiffré */}
            <div className="text-center mt-2" style={{ fontSize: '0.82rem', color: '#495057', lineHeight: 1.6 }}>
                <div>
                    <span className="text-muted">{trans.total_invoiced ?? 'CA facturé'} : </span>
                    <strong>{formatCurrency(invoiced, currency, locale)}</strong>
                </div>
                <div>
                    <span className="text-muted">{trans.annual_target ?? 'Objectif'} : </span>
                    <strong>{formatCurrency(target, currency, locale)}</strong>
                </div>
            </div>

            {/* Barre de progression Bootstrap */}
            <div className="progress mt-3" style={{ height: 8, borderRadius: 4 }}>
                <div
                    className={`progress-bar ${pct >= 100 ? 'bg-success' : pct >= 75 ? 'bg-info' : pct >= 50 ? 'bg-warning' : 'bg-danger'}`}
                    role="progressbar"
                    style={{ width: `${clamp(pct, 0, 100)}%`, transition: 'width 0.6s ease' }}
                    aria-valuenow={Math.round(pct)}
                    aria-valuemin="0"
                    aria-valuemax="100"
                />
            </div>
            <div className="text-right mt-1" style={{ fontSize: '0.72rem', color: '#6c757d' }}>
                {formatCurrency(Math.max(target - invoiced, 0), currency, locale)} {trans.remaining ?? 'restant'}
            </div>
        </WidgetCard>
    );
}
