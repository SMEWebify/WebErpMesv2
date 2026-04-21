import React, { useEffect, useState } from 'react';

function fmt(n) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0);
}

function TotalCard({ total, loading }) {
    return (
        <div className="small-box bg-info mb-3">
            <div className="inner">
                <h3>{loading ? '…' : fmt(total) + ' €'}</h3>
                <p>Valeur totale du stock (CUMP)</p>
            </div>
            <div className="icon"><i className="fas fa-boxes"></i></div>
        </div>
    );
}

export default function StockValuationApp({ endpoints, trans }) {
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
            .catch(() => setError('Erreur de chargement'))
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
                <div className="input-group-prepend">
                    <span className="input-group-text"><i className="fas fa-search"></i></span>
                </div>
                <input
                    type="text"
                    className="form-control"
                    placeholder="Filtrer produit / emplacement…"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                />
            </div>

            <div className="table-responsive">
                <table className="table table-sm table-hover">
                    <thead className="thead-light">
                        <tr>
                            <th>Référence</th>
                            <th>Produit</th>
                            <th>Emplacement</th>
                            <th className="text-right">Qté nette</th>
                            <th className="text-right">CUMP (€)</th>
                            <th className="text-right">Valeur (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading && (
                            <tr><td colSpan={6} className="text-center py-3">
                                <i className="fas fa-spinner fa-spin mr-2"></i>Chargement…
                            </td></tr>
                        )}
                        {!loading && filtered.length === 0 && (
                            <tr><td colSpan={6} className="text-center text-muted py-3">Aucune donnée</td></tr>
                        )}
                        {filtered.map((r, i) => (
                            <tr key={i}>
                                <td><code>{r.product_code}</code></td>
                                <td>{r.product_label}</td>
                                <td><span className="badge badge-secondary">{r.location_code}</span></td>
                                <td className="text-right">{fmt(r.net_qty)}</td>
                                <td className="text-right">{fmt(r.unit_cost)}</td>
                                <td className="text-right font-weight-bold">{fmt(r.total_value)}</td>
                            </tr>
                        ))}
                    </tbody>
                    {!loading && filtered.length > 0 && (
                        <tfoot>
                            <tr className="table-info font-weight-bold">
                                <td colSpan={5} className="text-right">Total</td>
                                <td className="text-right">
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
