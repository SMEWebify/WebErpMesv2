import React, { useCallback, useEffect, useMemo, useState } from 'react';

// Réapprovisionnement : propositions d'achat calculées par ReorderService
// (seuil mini + besoins réservés − stock − en commande), éditables, puis une
// commande d'achat BROUILLON par fournisseur. Rien n'est envoyé au fournisseur.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const fmt = (n, d = 3) => Number(n || 0).toLocaleString('fr-FR', { maximumFractionDigits: d });
const money = (n, cur) => `${Number(n || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 3 })} ${cur}`;

/** Prix par quantité du fournisseur, sinon prix d'achat de l'article. */
function priceFor(row, supplierId, qty) {
    const breaks = row.price_breaks.filter((b) => String(b.companies_id) === String(supplierId))
        .filter((b) => qty >= b.min_qty && (!b.max_qty || qty <= b.max_qty))
        .sort((a, b) => b.min_qty - a.min_qty);
    return breaks.length ? breaks[0].price : row.purchased_price;
}

export default function ReorderPage({ endpoints, scope = 'all', products = [], currency = '€' }) {
    const [rows, setRows] = useState([]);
    const [suppliers, setSuppliers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const [created, setCreated] = useState(null);

    const load = useCallback(async () => {
        setLoading(true); setError(null); setCreated(null);
        const params = new URLSearchParams();
        params.set('scope', scope);
        products.forEach((id) => params.append('products[]', id));
        try {
            const res = await fetch(`${endpoints.json}?${params}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(`Erreur ${res.status}`);
            const data = await res.json();
            setSuppliers(data.suppliers);
            setRows(data.rows.map((r) => ({
                ...r,
                selected: r.suggested_qty > 0,
                qty: r.suggested_qty,
                supplier: r.supplier_id ?? '',
                price: priceFor(r, r.supplier_id, r.suggested_qty),
                priceTouched: false,
            })));
        } catch (e) {
            setError(e.message || String(e));
        }
        setLoading(false);
    }, [endpoints.json, scope, products]);

    useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

    const update = (id, patch) => setRows((prev) => prev.map((r) => {
        if (r.product_id !== id) return r;
        const next = { ...r, ...patch };
        if (!next.priceTouched && ('qty' in patch || 'supplier' in patch)) next.price = priceFor(next, next.supplier, +next.qty || 0);
        return next;
    }));

    const selected = rows.filter((r) => r.selected && +r.qty > 0);
    const missingSupplier = selected.filter((r) => !r.supplier);
    const groups = useMemo(() => {
        const g = new Map();
        selected.filter((r) => r.supplier).forEach((r) => {
            const k = String(r.supplier);
            if (!g.has(k)) g.set(k, { lines: 0, total: 0 });
            const e = g.get(k); e.lines += 1; e.total += (+r.qty) * (+r.price || 0);
        });
        return g;
    }, [selected]);
    const supplierLabel = (id) => suppliers.find((s) => String(s.id) === String(id))?.label ?? `#${id}`;

    async function submit() {
        setSaving(true); setError(null);
        try {
            const res = await fetch(endpoints.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({
                    lines: selected.map((r) => ({
                        product_id: r.product_id, companies_id: +r.supplier, qty: +r.qty, price: +r.price || 0,
                        stock_locations_id: r.stock_locations_id,
                    })),
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || `Erreur ${res.status}`));
            } else {
                setCreated(data.purchases);
            }
        } catch (e) {
            setError(e.message || String(e));
        }
        setSaving(false);
    }

    if (created) {
        return (
            <div className="card">
                <div className="card-body">
                    <h5 className="text-success"><i className="fas fa-check-circle me-2" />{created.length} commande(s) d'achat créée(s) en brouillon</h5>
                    <p className="text-muted small">Relisez-les, ajustez si besoin, puis passez-les au statut « Commandé » pour les envoyer.</p>
                    <ul className="list-group mb-3">
                        {created.map((p) => (
                            <a key={p.id} href={p.url} className="list-group-item list-group-item-action d-flex justify-content-between">
                                <span><i className="fas fa-file-invoice me-2" />{p.code}</span>
                                <span className="text-muted">{p.lines} ligne(s)</span>
                            </a>
                        ))}
                    </ul>
                    <button type="button" className="btn btn-outline-secondary btn-sm" onClick={load}><i className="fas fa-redo me-1" />Nouvelle analyse</button>
                </div>
            </div>
        );
    }

    return (
        <div className="card">
            <div className="card-header d-flex align-items-center py-2">
                <span className="small text-muted">
                    À commander = seuil mini + besoins réservés − stock − déjà en commande (arrondi à la qté éco. d'achat).
                </span>
                <button type="button" className="btn btn-sm btn-link ms-auto" onClick={load} disabled={loading}><i className="fas fa-sync me-1" />Recalculer</button>
            </div>
            <div className="card-body p-0">
                {loading && <div className="p-3 text-muted"><i className="fas fa-spinner fa-spin me-2" />Analyse du stock…</div>}
                {!loading && rows.length === 0 && !error && (
                    <div className="p-4 text-center text-muted">
                        <i className="fas fa-check-circle text-success fa-2x mb-2 d-block" />
                        Rien à commander : aucun article n'est sous son seuil mini.
                        {scope === 'tools' && <div className="small mt-1">Seuls les outils liés à un article de stock (avec un seuil mini) sont analysés.</div>}
                    </div>
                )}
                {!loading && rows.length > 0 && (
                    <div className="table-responsive">
                        <table className="table table-sm table-hover align-middle mb-0">
                            <thead className="table-light">
                                <tr>
                                    <th style={{ width: 32 }}>
                                        <input type="checkbox" className="form-check-input" aria-label="Tout sélectionner"
                                            checked={rows.every((r) => r.selected)}
                                            onChange={(e) => setRows((prev) => prev.map((r) => ({ ...r, selected: e.target.checked })))} />
                                    </th>
                                    <th>Article</th>
                                    <th className="text-end">Stock</th>
                                    <th className="text-end">Mini</th>
                                    <th className="text-end">Réservé</th>
                                    <th className="text-end">En commande</th>
                                    <th style={{ width: 100 }}>À commander</th>
                                    <th style={{ minWidth: 200 }}>Fournisseur</th>
                                    <th style={{ width: 120 }}>Prix unit.</th>
                                    <th className="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((r) => {
                                    const preferredIds = new Set(r.preferred_suppliers.map((s) => String(s.id)));
                                    return (
                                        <tr key={r.product_id} className={r.selected ? '' : 'text-muted'}>
                                            <td><input type="checkbox" className="form-check-input" checked={r.selected} onChange={(e) => update(r.product_id, { selected: e.target.checked })} /></td>
                                            <td>
                                                <a href={r.product_url} className="fw-semibold">{r.code}</a>
                                                <div className="small text-muted text-truncate" style={{ maxWidth: 260 }}>{r.label}</div>
                                            </td>
                                            <td className={`text-end ${r.stock < r.mini ? 'text-danger fw-semibold' : ''}`}>{fmt(r.stock)}</td>
                                            <td className="text-end">{fmt(r.mini)}</td>
                                            <td className="text-end">{fmt(r.needs)}</td>
                                            <td className="text-end">{fmt(r.on_order)}</td>
                                            <td>
                                                <input type="number" min="0" step="1" className="form-control form-control-sm text-end" value={r.qty}
                                                    onChange={(e) => update(r.product_id, { qty: e.target.value })} />
                                            </td>
                                            <td>
                                                <select className={`form-select form-select-sm ${r.selected && !r.supplier ? 'is-invalid' : ''}`} value={r.supplier}
                                                    onChange={(e) => update(r.product_id, { supplier: e.target.value })}>
                                                    <option value="">— choisir —</option>
                                                    {r.preferred_suppliers.length > 0 && (
                                                        <optgroup label="Fournisseurs préférés">
                                                            {r.preferred_suppliers.map((s) => <option key={s.id} value={s.id}>{s.label}</option>)}
                                                        </optgroup>
                                                    )}
                                                    <optgroup label="Tous les fournisseurs">
                                                        {suppliers.filter((s) => !preferredIds.has(String(s.id))).map((s) => <option key={s.id} value={s.id}>{s.label}</option>)}
                                                    </optgroup>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.001" className="form-control form-control-sm text-end" value={r.price}
                                                    onChange={(e) => update(r.product_id, { price: e.target.value, priceTouched: true })} />
                                            </td>
                                            <td className="text-end text-nowrap">{money((+r.qty || 0) * (+r.price || 0), currency)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
            {!loading && rows.length > 0 && (
                <div className="card-footer">
                    {groups.size > 0 && (
                        <div className="d-flex flex-wrap gap-2 mb-2 small">
                            {[...groups.entries()].map(([id, g]) => (
                                <span key={id} className="badge text-bg-light border">
                                    {supplierLabel(id)} · {g.lines} ligne(s) · {money(g.total, currency)}
                                </span>
                            ))}
                        </div>
                    )}
                    {missingSupplier.length > 0 && (
                        <div className="small text-danger mb-2">{missingSupplier.length} ligne(s) cochée(s) sans fournisseur.</div>
                    )}
                    {error && <div className="alert alert-danger py-2 small mb-2">{error}</div>}
                    <button type="button" className="btn btn-warning" disabled={saving || selected.length === 0 || missingSupplier.length > 0} onClick={submit}>
                        {saving ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-cart-plus me-1" />}
                        Créer {groups.size || ''} commande(s) d'achat en brouillon
                    </button>
                </div>
            )}
            {error && rows.length === 0 && <div className="card-footer"><div className="alert alert-danger py-2 small mb-0">{error}</div></div>}
        </div>
    );
}
