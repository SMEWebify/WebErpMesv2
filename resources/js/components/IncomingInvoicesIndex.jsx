import React, { useState, useEffect, useRef } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    received:           { badge: 'badge-info',      label: 'Reçue' },
    supplier_unmatched: { badge: 'badge-warning',   label: 'Fournisseur inconnu' },
    converted:          { badge: 'badge-success',   label: 'Convertie' },
    rejected:           { badge: 'badge-secondary', label: 'Refusée' },
    unreadable:         { badge: 'badge-danger',    label: 'Illisible' },
};

const ALL_STATUSES = ['received', 'supplier_unmatched', 'converted', 'rejected', 'unreadable'];

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function formatCurrency(amount, currency, locale) {
    if (amount === null || amount === undefined) return '—';
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency', currency: currency || 'EUR',
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${Number(amount).toFixed(2)} ${currency ?? '€'}`;
    }
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...options.headers,
        },
        ...options,
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(body.message || `HTTP ${res.status}`);
        err.status = res.status;
        throw err;
    }
    return body;
}

function StatusBadge({ status }) {
    const cfg = STATUS_CONFIG[status] ?? { badge: 'badge-secondary', label: status };
    return <span className={`badge ${cfg.badge}`}>{cfg.label}</span>;
}

// ---------------------------------------------------------------------------
// Pagination (aligné sur les autres index)
// ---------------------------------------------------------------------------

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;
    const { current_page, last_page } = meta;
    const pages = [];
    for (let p = Math.max(1, current_page - 2); p <= Math.min(last_page, current_page + 2); p++) pages.push(p);

    return (
        <nav>
            <ul className="pagination pagination-sm justify-content-center mb-0">
                <li className={`page-item ${current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page - 1)}>&laquo;</button>
                </li>
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                <li className={`page-item ${current_page === last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(current_page + 1)}>&raquo;</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Upload card
// ---------------------------------------------------------------------------

function UploadCard({ uploadUrl, onUploaded }) {
    const [busy, setBusy]   = useState(false);
    const [msg, setMsg]     = useState(null);
    const [err, setErr]     = useState(null);
    const fileRef = useRef(null);

    const submit = (e) => {
        e.preventDefault();
        const file = fileRef.current?.files?.[0];
        if (!file) return;

        const form = new FormData();
        form.append('document', file);

        setBusy(true); setMsg(null); setErr(null);
        apiFetch(uploadUrl, { method: 'POST', body: form })
            .then(res => {
                setMsg(res.message);
                if (fileRef.current) fileRef.current.value = '';
                onUploaded();
            })
            .catch(e => setErr(e.message))
            .finally(() => setBusy(false));
    };

    return (
        <div className="card">
            <div className="card-header"><h3 className="card-title">Déposer une facture électronique reçue</h3></div>
            <div className="card-body">
                <form onSubmit={submit} className="form-inline">
                    <input ref={fileRef} type="file" name="document" accept=".pdf,.xml" className="form-control-file mr-2" required />
                    <button type="submit" className="btn btn-primary" disabled={busy}>
                        <i className={`fas ${busy ? 'fa-spinner fa-spin' : 'fa-upload'} mr-1`} />
                        Importer (PDF Factur-X ou XML)
                    </button>
                </form>
                {msg && <div className="alert alert-success mt-2 mb-0 py-2">{msg}</div>}
                {err && <div className="alert alert-danger mt-2 mb-0 py-2">{err}</div>}
                <small className="text-muted d-block mt-2">
                    Le vendeur est rapproché automatiquement par n° de TVA intracom ou SIREN.
                </small>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function IncomingInvoicesIndex({ endpoints = {}, locale, currency }) {
    const [rows,    setRows]    = useState([]);
    const [meta,    setMeta]    = useState(null);
    const [loading, setLoading] = useState(false);
    const [error,   setError]   = useState(null);
    const [status,  setStatus]  = useState('');
    const [page,    setPage]    = useState(1);
    const [acting,  setActing]  = useState(null);

    const fetchData = (opts = {}) => {
        const params = new URLSearchParams();
        const st = opts.status ?? status;
        if (st) params.set('status', st);
        params.set('page', opts.page ?? page);

        setLoading(true); setError(null);
        apiFetch(`${endpoints.list}?${params}`)
            .then(res => { setRows(res.data); setMeta(res.meta); })
            .catch(e => setError(e.message))
            .finally(() => setLoading(false));
    };

    useEffect(() => { fetchData(); }, []);

    const handleStatus = (st) => { setStatus(st); setPage(1); fetchData({ status: st, page: 1 }); };
    const handlePage   = (p)  => { setPage(p); fetchData({ page: p }); };

    const doAction = (row, url, confirmMsg) => {
        if (confirmMsg && !window.confirm(confirmMsg)) return;
        setActing(row.id);
        apiFetch(url, { method: 'POST' })
            .then(res => {
                if (res.redirect) { window.location.href = res.redirect; return; }
                fetchData();
            })
            .catch(e => { setError(e.message); setActing(null); });
    };

    return (
        <div>
            <UploadCard uploadUrl={endpoints.upload} onUploaded={() => fetchData({ page: 1 })} />

            <div className="card">
                <div className="card-body pb-2">
                    <div className="btn-group btn-group-sm" role="group">
                        <button className={`btn ${status === '' ? 'btn-secondary' : 'btn-outline-secondary'}`} onClick={() => handleStatus('')}>
                            Toutes
                        </button>
                        {ALL_STATUSES.map(st => (
                            <button key={st}
                                className={`btn ${status === st ? STATUS_CONFIG[st].badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                                onClick={() => handleStatus(st)}>
                                {STATUS_CONFIG[st].label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="card-body p-0">
                    {error && <div className="alert alert-danger mx-3">{error}</div>}
                    {loading ? (
                        <div className="text-center py-5"><i className="fas fa-spinner fa-spin fa-2x text-muted" /></div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Vendeur</th>
                                        <th>N° facture</th>
                                        <th>Date</th>
                                        <th className="text-right">TTC</th>
                                        <th>Fournisseur WEM</th>
                                        <th className="text-center">Statut</th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.length === 0 ? (
                                        <tr><td colSpan={8} className="text-center text-muted py-4">Aucune facture reçue.</td></tr>
                                    ) : rows.map(row => (
                                        <tr key={row.id}>
                                            <td><span className="badge badge-light">{row.provider}</span></td>
                                            <td>{row.seller_name}<br /><small className="text-muted">{row.seller_vat}</small></td>
                                            <td>{row.invoice_number}</td>
                                            <td>{row.issue_date ?? '—'}</td>
                                            <td className="text-right">{formatCurrency(row.total_ttc, row.currency, locale)}</td>
                                            <td>{row.supplier?.label ?? <span className="text-muted">—</span>}</td>
                                            <td className="text-center"><StatusBadge status={row.status} /></td>
                                            <td className="text-right text-nowrap">
                                                {row.purchase_invoice_url && (
                                                    <a href={row.purchase_invoice_url} className="btn btn-sm btn-outline-success">
                                                        Facture d'achat
                                                    </a>
                                                )}
                                                {row.convert_url && (
                                                    <button className="btn btn-sm btn-success" disabled={acting === row.id}
                                                        onClick={() => doAction(row, row.convert_url)}>
                                                        Convertir
                                                    </button>
                                                )}
                                                {row.reject_url && (
                                                    <button className="btn btn-sm btn-outline-secondary ml-1" disabled={acting === row.id}
                                                        onClick={() => doAction(row, row.reject_url, 'Refuser cette facture ?')}>
                                                        Refuser
                                                    </button>
                                                )}
                                                {row.status === 'supplier_unmatched' && (
                                                    <span className="text-muted small ml-1">TVA {row.seller_vat}</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {meta && (
                    <div className="card-footer d-flex align-items-center justify-content-between">
                        <small className="text-muted">{meta.total} résultats</small>
                        <Pagination meta={meta} onPage={handlePage} />
                    </div>
                )}
            </div>
        </div>
    );
}
