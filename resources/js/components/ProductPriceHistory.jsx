import React, { useState, useEffect } from 'react';

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(y, m - 1, d));
    } catch {
        return dateStr;
    }
}

function formatPrice(price, currency) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 3 }).format(price) + ' ' + currency;
}

function PriceTable({ title, icon, color, rows, currency, sectionKey }) {
    if (rows.length === 0) {
        return (
            <div className="mb-4" data-section={sectionKey}>
                <h6 style={{ color }} className="mb-2">
                    <i className={`${icon} mr-1`} /> {title}
                </h6>
                <p className="text-muted small">Aucun historique.</p>
            </div>
        );
    }

    return (
        <div className="mb-4" data-section={sectionKey}>
            <h6 style={{ color }} className="mb-2">
                <i className={`${icon} mr-1`} /> {title}
            </h6>
            <div className="table-responsive">
                <table className="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Début</th>
                            <th>Fin</th>
                            <th>Prix</th>
                            <th>Durée</th>
                            <th>Par</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map(row => {
                            const isCurrent = !row.ended_at;
                            let duration = '—';
                            if (row.started_at) {
                                const start = new Date(row.started_at);
                                const end   = row.ended_at ? new Date(row.ended_at) : new Date();
                                const days  = Math.round((end - start) / 86400000);
                                duration    = days <= 0 ? '< 1 j' : `${days} j`;
                            }
                            return (
                                <tr key={row.id} style={isCurrent ? { fontWeight: 600, background: '#f0fff4' } : {}}>
                                    <td>{formatDate(row.started_at)}</td>
                                    <td>
                                        {isCurrent
                                            ? <span className="badge badge-success">En cours</span>
                                            : formatDate(row.ended_at)}
                                    </td>
                                    <td>{formatPrice(row.price, currency)}</td>
                                    <td className="text-muted">{duration}</td>
                                    <td className="text-muted">{row.user ?? '—'}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default function ProductPriceHistory({ endpoint, currency = '€', canPurchases = true }) {
    const [data,    setData]    = useState(null);
    const [loading, setLoading] = useState(true);
    const [error,   setError]   = useState(null);

    useEffect(() => {
        fetch(endpoint, { headers: { Accept: 'application/json' } })
            .then(r => { if (!r.ok) throw new Error(r.statusText); return r.json(); })
            .then(d  => { setData(d); setLoading(false); })
            .catch(e => { setError(e.message); setLoading(false); });
    }, [endpoint]);

    if (loading) return (
        <div className="text-center text-muted py-4">
            <i className="fas fa-spinner fa-spin mr-2" /> Chargement…
        </div>
    );

    if (error) return <div className="alert alert-danger">{error}</div>;

    return (
        <div>
            {canPurchases && (
                <PriceTable
                    title="Prix d'achat"
                    icon="fas fa-cart-arrow-down"
                    color="#6f42c1"
                    rows={data?.purchase ?? []}
                    currency={currency}
                    sectionKey="purchase"
                />
            )}
            <PriceTable
                title="Prix de vente"
                icon="fas fa-tag"
                color="#28a745"
                rows={data?.sale ?? []}
                currency={currency}
                sectionKey="sale"
            />
        </div>
    );
}
