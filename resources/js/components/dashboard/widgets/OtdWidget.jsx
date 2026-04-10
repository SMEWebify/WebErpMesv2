import React, { useState, useEffect } from 'react';
import WidgetCard from '../WidgetCard.jsx';

function clamp(v, min, max) { return Math.min(Math.max(v, min), max); }

function RadialGauge({ pct, size = 180 }) {
    const cx = size / 2, cy = size / 2;
    const R  = size * 0.38;
    const stroke = size * 0.09;

    const color = pct >= 95 ? '#28a745'
        : pct >= 85 ? '#17a2b8'
        : pct >= 70 ? '#ffc107'
        : '#dc3545';

    function arcPath(from, to) {
        const x1 = cx + R * Math.cos(from), y1 = cy + R * Math.sin(from);
        const x2 = cx + R * Math.cos(to),   y2 = cy + R * Math.sin(to);
        const large = Math.abs(to - from) > Math.PI ? 1 : 0;
        return `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2}`;
    }

    const fillAngle = Math.PI - (clamp(pct, 0, 100) / 100) * Math.PI;

    return (
        <svg viewBox={`0 0 ${size} ${size / 2 + stroke}`} style={{ width: '100%', maxWidth: size, display: 'block', margin: '0 auto' }}>
            <path d={arcPath(Math.PI, 0)} fill="none" stroke="#e9ecef" strokeWidth={stroke} strokeLinecap="round" />
            {pct > 0 && (
                <path d={arcPath(Math.PI, fillAngle)} fill="none" stroke={color} strokeWidth={stroke} strokeLinecap="round" style={{ transition: 'stroke-dashoffset 0.6s ease' }} />
            )}
            <text x={cx} y={cy - 2} textAnchor="middle" fontSize={size * 0.18} fontWeight="700" fill={color}>
                {clamp(Math.round(pct), 0, 100)}%
            </text>
        </svg>
    );
}

/**
 * OtdWidget — taux de service / On-Time Delivery
 *
 * Mode 1 — données pré-chargées :
 *   <OtdWidget data={{ rate: 87.45, onTime: 152, total: 174 }} trans={trans} />
 *
 * Mode 2 — fetch autonome :
 *   <OtdWidget endpoint="/kpi/otd" trans={trans} />
 */
export default function OtdWidget({
    data: initialData,
    endpoint,
    trans    = {},
    editMode = false,
    onRemove,
}) {
    const [data,    setData]    = useState(initialData ?? null);
    const [loading, setLoading] = useState(!initialData && !!endpoint);
    const [error,   setError]   = useState(null);

    useEffect(() => {
        if (initialData || !endpoint) return;
        setLoading(true);
        fetch(endpoint, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            credentials: 'same-origin',
        })
            .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
            .then(d => { setData(d); setLoading(false); })
            .catch(e => { setError(e.message); setLoading(false); });
    }, [endpoint]);

    useEffect(() => { if (initialData) setData(initialData); }, [initialData]);

    const isEmpty = !loading && !error && (data === null || data?.total === 0);
    const rate    = parseFloat(data?.rate ?? 0);

    const label = rate >= 95 ? (trans.otd_excellent ?? 'Excellent')
        : rate >= 85 ? (trans.otd_good ?? 'Bon')
        : rate >= 70 ? (trans.otd_average ?? 'Moyen')
        : (trans.otd_critical ?? 'Critique');

    return (
        <WidgetCard
            title={trans.otd_title ?? 'Taux de service (OTD)'}
            icon="fa-clock"
            theme="teal"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_delivery_data ?? 'Aucune livraison enregistrée'}
            editMode={editMode}
            onRemove={onRemove}
        >
            {data && data.total > 0 && (
                <>
                    <RadialGauge pct={rate} />

                    <div className="text-center mt-1" style={{ fontSize: '0.82rem', lineHeight: 1.7 }}>
                        <div>
                            <strong style={{ color: rate >= 85 ? '#28a745' : rate >= 70 ? '#ffc107' : '#dc3545' }}>
                                {label}
                            </strong>
                        </div>
                        <div className="text-muted">
                            {data.onTime ?? '—'} / {data.total ?? '—'} {trans.deliveries_on_time ?? 'livraisons à l\'heure'}
                        </div>
                    </div>

                    {/* Barre de progression */}
                    <div className="progress mt-2" style={{ height: 6, borderRadius: 3 }}>
                        <div
                            className={`progress-bar ${rate >= 95 ? 'bg-success' : rate >= 85 ? 'bg-info' : rate >= 70 ? 'bg-warning' : 'bg-danger'}`}
                            style={{ width: `${clamp(rate, 0, 100)}%`, transition: 'width 0.6s ease' }}
                        />
                    </div>
                </>
            )}
        </WidgetCard>
    );
}
