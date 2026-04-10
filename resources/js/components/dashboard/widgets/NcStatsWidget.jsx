import React, { useState, useEffect } from 'react';
import WidgetCard from '../WidgetCard.jsx';

function StatPill({ value, label, color }) {
    return (
        <div style={{
            flex: 1, textAlign: 'center', padding: '0.6rem 0.4rem',
            borderRadius: 8, background: color + '18', border: `1px solid ${color}33`,
        }}>
            <div style={{ fontSize: '1.6rem', fontWeight: 700, color, lineHeight: 1 }}>{value ?? 0}</div>
            <div style={{ fontSize: '0.72rem', color: '#6c757d', marginTop: 4 }}>{label}</div>
        </div>
    );
}

function RateBar({ internal, label }) {
    const ext = 100 - (internal ?? 0);
    return (
        <div style={{ marginTop: '0.75rem' }}>
            <div className="d-flex justify-content-between" style={{ fontSize: '0.75rem', marginBottom: 3 }}>
                <span><span style={{ color: '#dc3545' }}>■</span> {label} interne {Math.round(internal ?? 0)}%</span>
                <span><span style={{ color: '#17a2b8' }}>■</span> externe {Math.round(ext)}%</span>
            </div>
            <div style={{ height: 6, background: '#17a2b8', borderRadius: 3, overflow: 'hidden' }}>
                <div style={{ height: '100%', width: `${internal ?? 0}%`, background: '#dc3545', borderRadius: 3 }} />
            </div>
        </div>
    );
}

/**
 * NcStatsWidget — NC ouvertes, actions ouvertes, dérogations ouvertes
 *
 * Mode 1 — données pré-chargées :
 *   <NcStatsWidget data={ncStats} trans={trans} />
 *
 * Mode 2 — fetch autonome :
 *   <NcStatsWidget endpoint="/kpi/nc-stats" trans={trans} />
 */
export default function NcStatsWidget({
    data: initialData,
    endpoint,
    trans    = {},
    urls     = {},
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

    const isEmpty = !loading && !error && !data;

    return (
        <WidgetCard
            title={trans.nc_title ?? 'Qualité — en cours'}
            icon="fa-exclamation-triangle"
            theme="danger"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_data ?? 'Aucune donnée'}
            editMode={editMode}
            onRemove={onRemove}
        >
            {data && (
                <>
                    {/* 3 compteurs */}
                    <div className="d-flex" style={{ gap: '0.5rem' }}>
                        <StatPill
                            value={data.totalNonConformitiesOpen}
                            label={trans.nc_open ?? 'NC ouvertes'}
                            color="#dc3545"
                        />
                        <StatPill
                            value={data.totalActionsOpen}
                            label={trans.actions_open ?? 'Actions ouvertes'}
                            color="#ffc107"
                        />
                        <StatPill
                            value={data.totalDerogationsOpen}
                            label={trans.derogations_open ?? 'Dérogations'}
                            color="#6c757d"
                        />
                    </div>

                    {/* Barres interne/externe */}
                    {data.internalNonConformityRate != null && (
                        <RateBar
                            internal={data.internalNonConformityRate}
                            label="NC"
                        />
                    )}

                    {/* Lien vers qualité */}
                    {urls.quality && (
                        <div style={{ textAlign: 'right', marginTop: '0.5rem' }}>
                            <a href={urls.quality} className="btn btn-sm btn-outline-secondary" style={{ fontSize: '0.75rem' }}>
                                {trans.view_all ?? 'Voir tout'}
                            </a>
                        </div>
                    )}
                </>
            )}
        </WidgetCard>
    );
}
