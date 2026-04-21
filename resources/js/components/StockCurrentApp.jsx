import React, { useState } from 'react';

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const STATUS_CONFIG = {
    success: { label: 'Stock OK',  icon: 'fas fa-check-circle', badgeClass: 'badge-success' },
    warning: { label: 'Seuil min', icon: 'fas fa-exclamation-triangle', badgeClass: 'badge-warning' },
    danger:  { label: 'Rupture',   icon: 'fas fa-times-circle',  badgeClass: 'badge-danger'  },
};

function SummaryBar({ products }) {
    const counts = products.reduce(
        (acc, p) => { acc[p.status_color] = (acc[p.status_color] ?? 0) + 1; return acc; },
        {}
    );
    return (
        <div className="row mb-3">
            {Object.entries(STATUS_CONFIG).map(([color, cfg]) => (
                <div className="col-md-4" key={color}>
                    <div className={`small-box bg-${color}`} style={{ marginBottom: 0 }}>
                        <div className="inner">
                            <h3>{counts[color] ?? 0}</h3>
                            <p>{cfg.label}</p>
                        </div>
                        <div className="icon">
                            <i className={cfg.icon}></i>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function StatusBadge({ color }) {
    const cfg = STATUS_CONFIG[color] ?? STATUS_CONFIG.danger;
    return (
        <span className={`badge ${cfg.badgeClass}`} style={{ fontSize: '0.8em', whiteSpace: 'nowrap' }}>
            <i className={`${cfg.icon} mr-1`}></i>
            {cfg.label}
        </span>
    );
}

function ProductRow({ product, storeOrderUrl }) {
    const [ordering, setOrdering] = useState(false);
    const [error, setError]       = useState(null);

    async function handleCreateOrder() {
        if (!confirm(`Créer une commande interne pour "${product.label}" (qté : ${product.qty_need}) ?`)) return;
        setOrdering(true);
        setError(null);
        try {
            const res = await fetch(storeOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({ product_id: product.id, qty: product.qty_need }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error ?? 'Erreur serveur');
            window.location.href = data.redirect_url;
        } catch (err) {
            setError(err.message);
            setOrdering(false);
        }
    }

    return (
        <tr>
            {/* Produit */}
            <td>
                <a href={product.product_url} className="font-weight-bold text-dark">
                    {product.label}
                </a>
            </td>

            {/* Statut */}
            <td className="text-center align-middle">
                <StatusBadge color={product.status_color} />
            </td>

            {/* Qtés */}
            <td className="align-middle">
                <div className="d-flex flex-column">
                    <small className="text-muted">Stock / Besoin</small>
                    <span>
                        <strong>{product.total_stock_move}</strong>
                        <span className="text-muted mx-1">/</span>
                        <strong className={product.status_color === 'danger' ? 'text-danger' : ''}>
                            {product.qty_need}
                        </strong>
                    </span>
                    {(product.undelivered_qty > 0 || product.task_qty > 0) && (
                        <small className="text-muted">
                            OF : {product.undelivered_qty} · Tâches : {product.task_qty}
                        </small>
                    )}
                </div>
            </td>

            {/* Emplacements */}
            <td className="align-middle">
                {product.locations.length === 0 ? (
                    <span className="text-muted">—</span>
                ) : (
                    <div className="d-flex flex-wrap gap-1" style={{ gap: '4px' }}>
                        {product.locations.map(loc => (
                            <a
                                key={loc.id}
                                href={loc.url}
                                className={`btn btn-xs btn-${loc.color}`}
                                title={`Qté : ${loc.current_stock} (min : ${loc.mini_qty})`}
                            >
                                {loc.code}
                                <span className="ml-1 badge badge-light text-dark">{loc.current_stock}</span>
                            </a>
                        ))}
                    </div>
                )}
            </td>

            {/* Actions */}
            <td className="align-middle text-center">
                <a href={product.product_url} className="btn btn-xs btn-default mr-1" title="Voir le produit">
                    <i className="fas fa-eye"></i>
                </a>
                {product.qty_need > 0 && (
                    <button
                        className="btn btn-xs btn-primary"
                        onClick={handleCreateOrder}
                        disabled={ordering}
                        title="Créer une commande interne"
                    >
                        <i className={`fas ${ordering ? 'fa-spinner fa-spin' : 'fa-folder-plus'}`}></i>
                    </button>
                )}
                {error && <div className="text-danger mt-1"><small>{error}</small></div>}
            </td>
        </tr>
    );
}

export default function StockCurrentApp({ endpoints, trans }) {
    const [products, setProducts] = useState([]);
    const [loading, setLoading]   = useState(false);
    const [loaded, setLoaded]     = useState(false);
    const [error, setError]       = useState(null);
    const [filter, setFilter]     = useState('all');

    async function loadStock() {
        setLoading(true);
        setError(null);
        try {
            const res = await fetch(endpoints.current, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            });
            if (!res.ok) throw new Error('Erreur chargement stock');
            setProducts(await res.json());
            setLoaded(true);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }

    const filtered = filter === 'all' ? products : products.filter(p => p.status_color === filter);

    return (
        <div>
            {/* Notes */}
            {trans.note  && <div className="callout callout-info mb-2"><p>{trans.note}</p></div>}
            {trans.note2 && <div className="callout callout-info mb-2"><p>{trans.note2}</p></div>}

            {/* Bouton de chargement */}
            {!loaded && (
                <button className="btn btn-success" onClick={loadStock} disabled={loading}>
                    {loading
                        ? <><i className="fas fa-spinner fa-spin mr-1"></i>Chargement...</>
                        : <><i className="fas fa-boxes mr-1"></i>{trans.view_stock ?? 'Voir le stock actuel'}</>
                    }
                </button>
            )}

            {error && <div className="alert alert-danger mt-2">{error}</div>}

            {loaded && (
                <>
                    {/* Bandeau synthèse */}
                    <SummaryBar products={products} />

                    {/* Filtres */}
                    <div className="mb-3">
                        {[
                            { key: 'all',     label: 'Tous' },
                            { key: 'danger',  label: 'Rupture' },
                            { key: 'warning', label: 'Seuil min' },
                            { key: 'success', label: 'Stock OK' },
                        ].map(f => (
                            <button
                                key={f.key}
                                className={`btn btn-sm mr-1 ${filter === f.key ? 'btn-dark' : 'btn-outline-secondary'}`}
                                onClick={() => setFilter(f.key)}
                            >
                                {f.key !== 'all' && <i className={`${STATUS_CONFIG[f.key].icon} mr-1`}></i>}
                                {f.label}
                                <span className="badge badge-light ml-1 text-dark">
                                    {f.key === 'all' ? products.length : products.filter(p => p.status_color === f.key).length}
                                </span>
                            </button>
                        ))}
                        <button className="btn btn-sm btn-link float-right" onClick={loadStock}>
                            <i className="fas fa-sync-alt mr-1"></i>Actualiser
                        </button>
                    </div>

                    {/* Tableau */}
                    <div className="table-responsive">
                        <table className="table table-hover table-sm">
                            <thead className="thead-light">
                                <tr>
                                    <th>Produit</th>
                                    <th className="text-center">Statut</th>
                                    <th>Stock / Besoin</th>
                                    <th>Emplacements</th>
                                    <th className="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="text-center text-muted py-3">
                                            Aucun produit dans cette catégorie.
                                        </td>
                                    </tr>
                                ) : (
                                    filtered.map(product => (
                                        <ProductRow
                                            key={product.id}
                                            product={product}
                                            storeOrderUrl={endpoints.store_order}
                                        />
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </div>
    );
}
