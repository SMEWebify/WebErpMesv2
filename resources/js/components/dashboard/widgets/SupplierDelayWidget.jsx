import React, { useState, useEffect } from 'react';
import WidgetCard from '../WidgetCard.jsx';

function delayColor(days) {
    if (days === null || days === undefined) return '#6c757d';
    if (days <= 3)  return '#28a745';
    if (days <= 7)  return '#ffc107';
    if (days <= 14) return '#fd7e14';
    return '#dc3545';
}

function complianceBadge(rate) {
    if (rate === null || rate === undefined) return null;
    const pct = Math.round(rate * 100);
    const cls = pct >= 95 ? 'badge-success' : pct >= 85 ? 'badge-warning' : 'badge-danger';
    return <span className={`badge ${cls}`} style={{ fontSize: '0.7rem' }}>{pct}%</span>;
}

/**
 * SupplierDelayWidget — délai moyen de réception par fournisseur
 *
 * Mode 1 — données pré-chargées :
 *   <SupplierDelayWidget data={suppliers} trans={trans} />
 *
 * Mode 2 — fetch autonome :
 *   <SupplierDelayWidget endpoint="/kpi/supplier-delays" trans={trans} />
 */
export default function SupplierDelayWidget({
    data: initialData,
    endpoint,
    trans    = {},
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

    return (
        <WidgetCard
            title={trans.supplier_delay_title ?? 'Délai moyen fournisseurs'}
            icon="fa-shipping-fast"
            theme="info"
            loading={loading}
            error={error}
            empty={isEmpty}
            emptyText={trans.no_supplier_data ?? 'Aucune réception enregistrée'}
            editMode={editMode}
            onRemove={onRemove}
        >
            <div style={{ margin: '0 -0.75rem' }}>
                <table className="table table-sm m-0" style={{ fontSize: '0.8rem' }}>
                    <thead>
                        <tr style={{ background: '#f8f9fa' }}>
                            <th style={{ fontWeight: 600, padding: '0.3rem 0.75rem' }}>
                                {trans.supplier ?? 'Fournisseur'}
                            </th>
                            <th className="text-center" style={{ fontWeight: 600, padding: '0.3rem 0.5rem', whiteSpace: 'nowrap' }}>
                                {trans.avg_delay ?? 'Délai moy.'}
                            </th>
                            <th className="text-center" style={{ fontWeight: 600, padding: '0.3rem 0.75rem', whiteSpace: 'nowrap' }}>
                                {trans.compliance ?? 'Conformité'}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {(items ?? []).map((s, i) => {
                            const days = parseFloat(s.avg_reception_delay);
                            return (
                                <tr key={i}>
                                    <td style={{ padding: '0.35rem 0.75rem', maxWidth: 160, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                        {s.supplier_name}
                                    </td>
                                    <td className="text-center" style={{ padding: '0.35rem 0.5rem', whiteSpace: 'nowrap' }}>
                                        <span style={{ color: delayColor(days), fontWeight: 600 }}>
                                            {isNaN(days) ? '—' : `${Math.round(days)} j`}
                                        </span>
                                    </td>
                                    <td className="text-center" style={{ padding: '0.35rem 0.75rem' }}>
                                        {complianceBadge(s.compliance_rate) ?? <span className="text-muted">—</span>}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </WidgetCard>
    );
}
