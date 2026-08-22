import React, { useEffect, useMemo, useState } from 'react';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const STATUS_LABELS = {
    1: { key: 'status_draft',     badge: 'badge-secondary' },
    2: { key: 'status_exported',  badge: 'badge-info' },
    3: { key: 'status_validated', badge: 'badge-success' },
    4: { key: 'status_cancelled', badge: 'badge-danger' },
};

const SCOPE_LABEL_KEY = {
    all:      'scope_all',
    location: 'scope_location',
    category: 'scope_category',
};

export default function InventoriesIndex({ endpoints, trans, stockLocations, categories }) {
    const [inventories, setInventories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('');
    const [modalOpen, setModalOpen] = useState(false);

    async function reload() {
        setLoading(true);
        const url = statusFilter ? `${endpoints.indexJson}?status=${statusFilter}` : endpoints.indexJson;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        setInventories(json.inventories ?? []);
        setLoading(false);
    }

    useEffect(() => { reload(); }, [statusFilter]);

    const rows = useMemo(() => inventories, [inventories]);

    return (
        <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between">
                <div className="d-flex align-items-center">
                    <label className="mr-2 mb-0">{trans.status}:</label>
                    <select
                        className="form-control form-control-sm"
                        style={{ width: 200 }}
                        value={statusFilter}
                        onChange={e => setStatusFilter(e.target.value)}
                    >
                        <option value="">—</option>
                        <option value="1">{trans.status_draft}</option>
                        <option value="2">{trans.status_exported}</option>
                        <option value="3">{trans.status_validated}</option>
                        <option value="4">{trans.status_cancelled}</option>
                    </select>
                </div>
                <button className="btn btn-primary" onClick={() => setModalOpen(true)}>
                    <i className="fas fa-plus mr-1"></i>{trans.new_inventory}
                </button>
            </div>

            <div className="card-body p-0">
                <table className="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{trans.code}</th>
                            <th>{trans.label}</th>
                            <th>{trans.scope}</th>
                            <th>{trans.status}</th>
                            <th>{trans.created_at}</th>
                            <th>{trans.created_by}</th>
                            <th>{trans.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading && (
                            <tr><td colSpan={7} className="text-center text-muted p-3">…</td></tr>
                        )}
                        {!loading && rows.length === 0 && (
                            <tr><td colSpan={7} className="text-center text-muted p-3">{trans.no_results}</td></tr>
                        )}
                        {!loading && rows.map(inv => {
                            const statusMeta = STATUS_LABELS[inv.statu] ?? STATUS_LABELS[1];
                            const scopeKey = SCOPE_LABEL_KEY[inv.scope_type] ?? 'scope_all';
                            const createdAt = inv.created_at ? new Date(inv.created_at).toLocaleDateString(trans.locale) : '';
                            return (
                                <tr key={inv.id}>
                                    <td><code>{inv.code}</code></td>
                                    <td>{inv.label}</td>
                                    <td>{trans[scopeKey]}</td>
                                    <td><span className={`badge ${statusMeta.badge}`}>{trans[statusMeta.key]}</span></td>
                                    <td>{createdAt}</td>
                                    <td>{inv.creator?.name ?? ''}</td>
                                    <td>
                                        <a href={`${endpoints.show}/${inv.id}`} className="btn btn-sm btn-outline-primary">
                                            <i className="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {modalOpen && (
                <CreateInventoryModal
                    endpoints={endpoints}
                    trans={trans}
                    stockLocations={stockLocations}
                    categories={categories}
                    onClose={() => setModalOpen(false)}
                />
            )}
        </div>
    );
}

function CreateInventoryModal({ endpoints, trans, stockLocations, categories, onClose }) {
    const [label, setLabel] = useState(`Inventaire ${new Date().toLocaleDateString(trans.locale)}`);
    const [scopeType, setScopeType] = useState('all');
    const [scopeIds, setScopeIds] = useState([]);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);

    async function handleSubmit(e) {
        e.preventDefault();
        setSubmitting(true);
        setError(null);

        const form = new FormData();
        form.append('label', label);
        form.append('scope_type', scopeType);
        if (scopeType !== 'all') {
            scopeIds.forEach(id => form.append('scope_ids[]', id));
        }

        try {
            const res = await fetch(endpoints.store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: form,
            });

            if (!res.ok && res.status !== 302) {
                const text = await res.text();
                throw new Error(text || `HTTP ${res.status}`);
            }

            // The controller redirects to the show page; the browser follows
            // it because we submit a plain form-data POST expecting 302.
            window.location.href = res.url;
        } catch (err) {
            setError(err.message);
            setSubmitting(false);
        }
    }

    const optionsForScope = scopeType === 'location' ? stockLocations : categories;

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.4)' }}>
            <div className="modal-dialog modal-dialog-centered">
                <form className="modal-content" onSubmit={handleSubmit}>
                    <div className="modal-header">
                        <h5 className="modal-title">{trans.new_inventory}</h5>
                        <button type="button" className="close" onClick={onClose}>&times;</button>
                    </div>
                    <div className="modal-body">
                        <div className="alert alert-warning py-2">
                            <i className="fas fa-exclamation-triangle mr-1"></i>
                            {trans.pause_moves_warning}
                        </div>

                        <div className="form-group">
                            <label>{trans.label}</label>
                            <input
                                type="text"
                                className="form-control"
                                value={label}
                                onChange={e => setLabel(e.target.value)}
                            />
                        </div>

                        <div className="form-group">
                            <label>{trans.scope}</label>
                            <div>
                                <label className="mr-3">
                                    <input type="radio" checked={scopeType === 'all'} onChange={() => { setScopeType('all'); setScopeIds([]); }} />
                                    {' '}{trans.scope_all}
                                </label>
                                <label className="mr-3">
                                    <input type="radio" checked={scopeType === 'location'} onChange={() => { setScopeType('location'); setScopeIds([]); }} />
                                    {' '}{trans.scope_location}
                                </label>
                                <label>
                                    <input type="radio" checked={scopeType === 'category'} onChange={() => { setScopeType('category'); setScopeIds([]); }} />
                                    {' '}{trans.scope_category}
                                </label>
                            </div>
                        </div>

                        {scopeType !== 'all' && (
                            <div className="form-group">
                                <label>{scopeType === 'location' ? trans.select_locations : trans.select_categories}</label>
                                <select
                                    multiple
                                    className="form-control"
                                    size={Math.min(8, (optionsForScope || []).length + 1)}
                                    value={scopeIds.map(String)}
                                    onChange={e => {
                                        const selected = Array.from(e.target.selectedOptions).map(o => Number(o.value));
                                        setScopeIds(selected);
                                    }}
                                >
                                    {(optionsForScope || []).map(o => (
                                        <option key={o.id} value={o.id}>
                                            {o.code ? `${o.code} — ${o.label ?? o.name ?? ''}` : (o.label ?? o.name ?? o.id)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {error && <div className="alert alert-danger py-2 mt-2">{error}</div>}
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-secondary" onClick={onClose} disabled={submitting}>
                            {trans.cancel}
                        </button>
                        <button type="submit" className="btn btn-primary" disabled={submitting}>
                            {trans.create}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
