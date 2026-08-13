import React, { useState, useEffect, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const ARC_STATUS = {
    1: { badge: 'badge-secondary', key: 'status_draft' },
    2: { badge: 'badge-primary',   key: 'status_sent' },
    3: { badge: 'badge-success',   key: 'status_accepted' },
    4: { badge: 'badge-light',     key: 'status_superseded' },
};

const STATUS_DRAFT = 1;

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function apiHeaders() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    };
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, { headers: apiHeaders(), ...options });
    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw { status: res.status, errors: body.errors ?? {}, message: body.message ?? `HTTP ${res.status}` };
    }
    return res.json();
}

function endpointFor(template, id) {
    return template.replace('__ID__', id);
}

// ---------------------------------------------------------------------------
// SortIcon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;

    const cur  = meta.current_page;
    const last = meta.last_page;
    const visible = new Set([1, last, cur - 1, cur, cur + 1].filter(p => p >= 1 && p <= last));
    const sorted = [...visible].sort((a, b) => a - b);

    return (
        <nav className="d-flex justify-content-between align-items-center px-3 pb-2">
            <small className="text-muted">{meta.total} résultat{meta.total > 1 ? 's' : ''}</small>
            <ul className="pagination pagination-sm mb-0">
                <li className={`page-item ${cur === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(cur - 1)}>«</button>
                </li>
                {sorted.map((p, i) => (
                    <React.Fragment key={p}>
                        {i > 0 && sorted[i - 1] < p - 1 && (
                            <li className="page-item disabled"><span className="page-link">…</span></li>
                        )}
                        <li className={`page-item ${p === cur ? 'active' : ''}`}>
                            <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                        </li>
                    </React.Fragment>
                ))}
                <li className={`page-item ${cur === last ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(cur + 1)}>»</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Alert flash
// ---------------------------------------------------------------------------

function Flash({ msg, type, onClose }) {
    if (!msg) return null;
    return (
        <div className={`alert alert-${type} alert-dismissible`} role="alert">
            {msg}
            <button type="button" className="close" onClick={onClose}><span>&times;</span></button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function OrderConfirmationsIndex({ endpoints, trans }) {
    const [rows, setRows]             = useState([]);
    const [meta, setMeta]             = useState(null);
    const [loading, setLoading]       = useState(true);
    const [fetchError, setFetchError] = useState('');
    const [page, setPage]             = useState(1);
    const [search, setSearch]         = useState('');
    const [status, setStatus]         = useState('');
    const [sort, setSort]             = useState({ field: 'created_at', asc: false });
    const [flash, setFlash]           = useState({ msg: '', type: 'success' });

    const showFlash = (msg, type = 'success') => {
        setFlash({ msg, type });
        setTimeout(() => setFlash({ msg: '', type: 'success' }), 4000);
    };

    const fetchData = useCallback(async () => {
        if (!endpoints?.list) {
            console.error('OrderConfirmationsIndex: endpoints.list is missing', endpoints);
            setLoading(false);
            setFetchError('Configuration manquante : endpoint list non défini');
            return;
        }
        setLoading(true);
        try {
            setFetchError('');
            const params = new URLSearchParams({ search, status, sort: sort.field, asc: sort.asc ? '1' : '0', page });
            const json = await apiFetch(`${endpoints.list}?${params}`);
            setRows(json.data ?? []);
            setMeta(json.meta ?? null);
        } catch (err) {
            console.error('OrderConfirmationsIndex fetch error:', err);
            setFetchError(err?.message ?? 'Erreur de chargement');
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [endpoints?.list, search, status, sort, page]);

    useEffect(() => { fetchData(); }, [fetchData]);

    function handleSort(field) {
        setSort(prev => ({ field, asc: prev.field === field ? !prev.asc : false }));
        setPage(1);
    }

    async function handleSend(row) {
        try {
            const res = await apiFetch(endpointFor(endpoints.send, row.id), { method: 'POST' });
            showFlash(res.message);
            fetchData();
        } catch (err) {
            showFlash(err.message, 'danger');
        }
    }

    const statusOptions = [
        { value: '1', label: trans.status_draft },
        { value: '2', label: trans.status_sent },
        { value: '3', label: trans.status_accepted },
        { value: '4', label: trans.status_superseded },
    ];

    return (
        <div>
            <Flash msg={flash.msg} type={flash.type} onClose={() => setFlash({ msg: '', type: 'success' })} />
            {fetchError && (
                <div className="alert alert-danger alert-dismissible py-2">
                    <i className="fas fa-exclamation-triangle mr-1" />{fetchError}
                    <button type="button" className="close py-1" onClick={() => setFetchError('')}><span>&times;</span></button>
                </div>
            )}

            <div className="card card-outline card-primary">
                <div className="card-header py-2">
                    <div className="d-flex flex-wrap align-items-center" style={{ gap: 8 }}>
                        <div className="input-group" style={{ maxWidth: 240 }}>
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-search" /></span>
                            </div>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                placeholder={trans.search}
                                value={search}
                                onChange={e => { setSearch(e.target.value); setPage(1); }}
                            />
                        </div>

                        <select
                            className="form-control form-control-sm"
                            style={{ maxWidth: 180 }}
                            value={status}
                            onChange={e => { setStatus(e.target.value); setPage(1); }}
                        >
                            <option value="">{trans.all}</option>
                            {statusOptions.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                    </div>
                </div>

                <div className="table-responsive">
                    <table className="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('code')}>
                                    {trans.code}<SortIcon field="code" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('revision')}>
                                    {trans.revision}<SortIcon field="revision" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th>{trans.order}</th>
                                <th>{trans.customer}</th>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('label')}>
                                    {trans.label}<SortIcon field="label" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th className="text-right">{trans.total}</th>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('statu')}>
                                    {trans.status}<SortIcon field="statu" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('created_at')}>
                                    {trans.created_at}<SortIcon field="created_at" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th>{trans.sent_at}</th>
                                <th>{trans.actions}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={10} className="text-center py-4">
                                        <i className="fas fa-spinner fa-spin" /> {trans.loading}
                                    </td>
                                </tr>
                            ) : rows.length === 0 ? (
                                <tr>
                                    <td colSpan={10} className="text-center py-4 text-muted">{trans.no_data}</td>
                                </tr>
                            ) : rows.map(row => {
                                const statusCfg = ARC_STATUS[row.statu] ?? { badge: 'badge-secondary', key: '' };
                                return (
                                    <tr
                                        key={row.id}
                                        style={{ cursor: 'pointer' }}
                                        onClick={() => { window.location.href = row.url; }}
                                    >
                                        <td><code>{row.code}</code></td>
                                        <td>
                                            <span className="badge badge-dark">{row.revision}</span>
                                            {row.is_current && <i className="fas fa-check-circle text-success ml-1" title="Indice en vigueur" />}
                                        </td>
                                        <td onClick={e => e.stopPropagation()}>
                                            {row.order
                                                ? <a href={row.order_url}>{row.order.code}</a>
                                                : '—'}
                                        </td>
                                        <td>{row.customer ?? '—'}</td>
                                        <td>{row.label}</td>
                                        <td className="text-right">{row.total}</td>
                                        <td>
                                            <span className={`badge ${statusCfg.badge}`}>
                                                {trans[statusCfg.key] ?? row.statu}
                                            </span>
                                        </td>
                                        <td>{row.created_at}</td>
                                        <td>{row.sent_at ?? '—'}</td>
                                        <td onClick={e => e.stopPropagation()}>
                                            <div className="d-flex flex-wrap" style={{ gap: 4 }}>
                                                {row.statu === STATUS_DRAFT && (
                                                    <button
                                                        className="btn btn-xs btn-outline-success"
                                                        title={trans.send}
                                                        onClick={() => handleSend(row)}
                                                    >
                                                        <i className="fas fa-paper-plane" />
                                                    </button>
                                                )}
                                                <a href={row.url} className="btn btn-xs btn-outline-primary" title={trans.view}>
                                                    <i className="fas fa-eye" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={meta} onPage={p => setPage(p)} />
            </div>
        </div>
    );
}
