import React, { useState, useEffect } from 'react';
import WidgetCard from '../WidgetCard.jsx';

function formatCurrency(value, currency, locale) {
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR', maximumFractionDigits: 0,
        }).format(value ?? 0);
    } catch {
        return (value ?? 0).toFixed(0);
    }
}

function HorizontalBar({ label, value, max, currency, locale, url, companieId }) {
    const pct = max > 0 ? Math.min((value / max) * 100, 100) : 0;
    const colors = ['#17a2b8', '#28a745', '#007bff', '#ffc107', '#6f42c1'];

    return (
        <div style={{ marginBottom: '0.6rem' }}>
            <div className="d-flex justify-content-between align-items-baseline mb-1" style={{ fontSize: '0.82rem' }}>
                <span style={{ fontWeight: 500, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '60%' }}>
                    {url && companieId
                        ? <a href={`${url}${companieId}`} style={{ color: 'inherit' }}>{label}</a>
                        : label}
                </span>
                <span style={{ color: '#6c757d', whiteSpace: 'nowrap', marginLeft: '0.5rem' }}>
                    {formatCurrency(value, currency, locale)}
                </span>
            </div>
            <div style={{ height: 8, background: '#e9ecef', borderRadius: 4, overflow: 'hidden' }}>
                <div style={{
                    height: '100%',
                    width: `${pct}%`,
                    background: colors[0],
                    borderRadius: 4,
                    transition: 'width 0.6s ease',
                }} />
            </div>
        </div>
    );
}

/**
 * TopClientsWidget — top N clients par CA facturé
 *
 * Mode 1 — données pré-chargées :
 *   <TopClientsWidget data={topClients} currency="EUR" locale="fr-FR" trans={trans} />
 *
 * Mode 2 — fetch autonome :
 *   <TopClientsWidget endpoint="/kpi/top-clients" currency="EUR" locale="fr-FR" trans={trans} />
 */
export default function TopClientsWidget({
    data: initialData,
    endpoint,
    currency = 'EUR',
    locale   = 'fr-FR',
    trans    = {},
    urls     = {},
    editMode = false,
    onRemove,
}) {
    const [items,   setItems]   = useState(() => Array.isArray(initialData) ? initialData : null);
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
            .then(d => { setItems(Array.isArray(d) ? d : []); setLoading(false); })
            .catch(e => { setError(e.message); setLoading(false); });
    }, [endpoint]);

    useEffect(() => {
        if (initialData) setItems(Array.isArray(initialData) ? initialData : []);
    }, [initialData]);

    const isEmpty = !loading && !error && (!items || items.length === 0);
    const max     = items ? Math.max(...items.map(c => parseFloat(c.total_amount) || 0)) : 0;

    return (
        <WidgetCard
            title={trans.top_clients ?? 'Top clients'}
            icon="fa-trophy"
            theme="warning"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_data ?? 'Aucune donnée'}
            editMode={editMode}
            onRemove={onRemove}
        >
            {(items ?? []).map((c, i) => (
                <HorizontalBar
                    key={c.companies_id ?? i}
                    label={c.companie?.label ?? `#${c.companies_id}`}
                    value={parseFloat(c.total_amount) || 0}
                    max={max}
                    currency={currency}
                    locale={locale}
                    url={urls.companies_show}
                    companieId={c.companies_id}
                />
            ))}
        </WidgetCard>
    );
}
