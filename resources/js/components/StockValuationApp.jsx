import React, { useEffect, useState } from 'react';

// Textes de l'écran (resources/lang/*/stock_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

function fmt(n) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0);
}

function TotalCard({ total, loading }) {
    return (
        <div className="small-box text-bg-info mb-3">
            <div className="inner">
                <h3>{loading ? '…' : fmt(total) + ' €'}</h3>
                <p>{L.total_value ?? 'Valeur totale du stock (CUMP)'}</p>
            </div>
            <i className="small-box-icon fas fa-boxes" aria-hidden="true" />
        </div>
    );
}

export default function StockValuationApp({ endpoints, trans }) {
    L = trans?.ui ?? {};
    const [total, setTotal]       = useState(0);
    const [rows, setRows]         = useState([]);
    const [loading, setLoading]   = useState(true);
    const [search, setSearch]     = useState('');
    const [error, setError]       = useState(null);

    useEffect(() => {
        fetch(endpoints.valuation, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => {
                setTotal(data.total ?? 0);
                setRows(data.breakdown ?? []);
            })
            .catch(() => setError((L.error_loading ?? 'Erreur de chargement')))
            .finally(() => setLoading(false));
    }, []);

    const filtered = rows.filter(r => {
        const q = search.toLowerCase();
        return (
            r.product_code?.toLowerCase().includes(q) ||
            r.product_label?.toLowerCase().includes(q) ||
            r.location_code?.toLowerCase().includes(q)
        );
    });

    return (
        <div>
            <TotalCard total={total} loading={loading} />

            {error && <div className="alert alert-danger">{error}</div>}

            <div className="input-group mb-3" style={{ maxWidth: 320 }}>
                <span className="input-group-text"><i className="fas fa-search"></i></span>
                <input
                    type="text"
                    className="form-control"
                    placeholder={L.filter_product_location ?? 'Filtrer produit / emplacement…'}
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                />
            </div>

            <div className="table-responsive">
                <table className="table table-sm table-hover">
                    <thead className="table-light">
                        <tr>
                            <th>{L.reference ?? 'Référence'}</th>
                            <th>{L.product ?? 'Produit'}</th>
                            <th>{L.location ?? 'Emplacement'}</th>
                            <th className="text-end">{L.net_qty ?? 'Qté nette'}</th>
                            <th className="text-end">{L.wac_eur ?? 'CUMP (€)'}</th>
                            <th className="text-end">{L.value_eur ?? 'Valeur (€)'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading && (
                            <tr><td colSpan={6} className="text-center py-3">
                                <i className="fas fa-spinner fa-spin me-2"></i>{L.loading ?? 'Chargement…'}
                            </td></tr>
                        )}
                        {!loading && filtered.length === 0 && (
                            <tr><td colSpan={6} className="text-center text-muted py-3">{L.no_data ?? 'Aucune donnée'}</td></tr>
                        )}
                        {filtered.map((r, i) => (
                            <tr key={i}>
                                <td><code>{r.product_code}</code></td>
                                <td>{r.product_label}</td>
                                <td><span className="badge text-bg-secondary">{r.location_code}</span></td>
                                <td className="text-end">{fmt(r.net_qty)}</td>
                                <td className="text-end">{fmt(r.unit_cost)}</td>
                                <td className="text-end fw-bold">{fmt(r.total_value)}</td>
                            </tr>
                        ))}
                    </tbody>
                    {!loading && filtered.length > 0 && (
                        <tfoot>
                            <tr className="table-info fw-bold">
                                <td colSpan={5} className="text-end">{L.total ?? 'Total'}</td>
                                <td className="text-end">
                                    {fmt(filtered.reduce((s, r) => s + parseFloat(r.total_value ?? 0), 0))} €
                                </td>
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>
        </div>
    );
}
